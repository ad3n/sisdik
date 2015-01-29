<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Entity\UserManager;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Langgas\SisdikBundle\Entity\Personil;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\Tahun;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/panitia-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PanitiaPendaftaranController extends Controller
{
    /**
     * @Route("/", name="regcommittee")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caritahun');

        $querybuilder = $em->createQueryBuilder()
            ->select('panitia')
            ->from('LanggasSisdikBundle:PanitiaPendaftaran', 'panitia')
            ->leftJoin('panitia.tahun', 'tahun')
            ->where('panitia.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder->andWhere('panitia.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/{id}/show", name="regcommittee_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * Mengaktifkan satu panitia, dan meniraktifkan yg lainnya.
     *
     * @Route("/{id}/activate", name="regcommittee_activate")
     */
    public function activateAction($id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        $results = $em->getRepository('LanggasSisdikBundle:Tahun')
            ->findBy([
                'sekolah' => $sekolah,
            ])
        ;
        $daftarTahun = [];
        foreach ($results as $tahun) {
            if (is_object($tahun) && $tahun instanceof Tahun) {
                $daftarTahun[] = $tahun->getId();
            }
        }

        $em->createQueryBuilder()
            ->update('LanggasSisdikBundle:PanitiaPendaftaran', 'panitia')
            ->set('panitia.aktif', '0')
            ->where('panitia.tahun IN (?1)')
            ->setParameter(1, $daftarTahun)
            ->getQuery()
            ->execute()
        ;

        $entity->setAktif(1);
        $entity->setDaftarPersonil($entity->getDaftarPersonil());
        $em->persist($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('regcommittee'));
    }

    /**
     * @Route("/new", name="regcommittee_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new PanitiaPendaftaran();

        $form = $this->createForm('sisdik_panitiapendaftaran', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="regcommittee_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PanitiaPendaftaran:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new PanitiaPendaftaran();
        $form = $this->createForm('sisdik_panitiapendaftaran', $entity);
        $form->submit($request);

        // prevent or remove empty personil to be inserted to database.
        $formdata = $form->getData();
        if (count($formdata->getDaftarPersonil()) > 1) {
            foreach ($formdata->getDaftarPersonil() as $personil) {
                if ($personil->getId() === null) {
                    $formdata->getDaftarPersonil()->removeElement($personil);
                }
            }
        } else {
            foreach ($formdata->getDaftarPersonil() as $personil) {
                if ($personil->getId() === null) {
                    $message = $this->get('translator')->trans('alert.regcommittee.notempty');
                    $form->get('daftarPersonil')->addError(new FormError($message));
                }
            }
        }

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);

                // give user the necessary role
                /* @var $em EntityManager */
                $userManager = $this->container->get('fos_user.user_manager');
                $daftarPersonil = $entity->getDaftarPersonil();
                if ($daftarPersonil instanceof ArrayCollection) {
                    foreach ($daftarPersonil as $personil) {
                        if ($personil instanceof Personil) {
                            if ($personil->getId() !== NULL) {
                                $user = $userManager->findUserBy([
                                    'id' => $personil->getId(),
                                ]);

                                $user->addRole('ROLE_PANITIA_PSB');
                                $userManager->updateUser($user);
                            }
                        }
                    }
                }

                $user = $userManager->findUserBy([
                    'id' => $entity->getKetuaPanitia()->getId()
                ]);
                $user->addRole('ROLE_KETUA_PANITIA_PSB');
                $userManager->updateUser($user);

                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.registration.committee.inserted', [
                        '%year%' => $entity->getTahun()->getTahun(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.registration.committee', [
                    '%year%' => $entity->getTahun()->getTahun(),
                ]);
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('regcommittee_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="regcommittee_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_panitiapendaftaran', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="regcommittee_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PanitiaPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        $entity->setPanitia(''); // prevent form generation read from database

        $editForm = $this->createForm('sisdik_panitiapendaftaran', $entity);
        $editForm->submit($request);

        // prevent or remove empty personil to be inserted to database.
        $formdata = $editForm->getData();
        if (count($formdata->getDaftarPersonil()) > 1) {
            foreach ($formdata->getDaftarPersonil() as $personil) {
                if ($personil->getId() === null) {
                    $formdata->getDaftarPersonil()->removeElement($personil);
                }
            }
        } else {
            foreach ($formdata->getDaftarPersonil() as $personil) {
                if ($personil->getId() === null) {
                    $message = $this->get('translator')->trans('alert.regcommittee.notempty');
                    $editForm->get('daftarPersonil')->addError(new FormError($message));
                }
            }
        }

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);

                // give user the necessary role
                /* @var $userManager UserManager */
                $userManager = $this->container->get('fos_user.user_manager');
                $daftarPersonil = $entity->getDaftarPersonil();
                if ($daftarPersonil instanceof ArrayCollection) {
                    foreach ($daftarPersonil as $personil) {
                        if ($personil instanceof Personil) {
                            if ($personil->getId() !== NULL) {
                                $user = $userManager->findUserBy([
                                    'id' => $personil->getId(),
                                ]);

                                if ($user instanceof User) {
                                    $user->addRole('ROLE_PANITIA_PSB');
                                    $userManager->updateUser($user);
                                }
                            }
                        }
                    }
                }

                $user = $userManager->findUserBy([
                    'id' => $entity->getKetuaPanitia()->getId()
                ]);
                $user->addRole('ROLE_KETUA_PANITIA_PSB');
                $userManager->updateUser($user);

                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.registration.committee.updated', [
                        '%year%' => $entity->getTahun()->getTahun(),
                    ]))
                ;

            } catch (DBALException $e) {
                $message = $this->get('translator')
                    ->trans('exception.unique.registration.committee', [
                        '%year%' => $entity->getTahun()->getTahun(),
                    ])
                ;
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('regcommittee_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="regcommittee_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
            }

            if ($this->get('security.context')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.registration.committee.deleted', [
                    '%year%' => $entity->getTahun()->getTahun(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.registration.committee.fail.delete', [
                    '%year%' => $entity->getTahun()->getTahun(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('regcommittee'));
    }

    /**
     * @Route("/name/{id}", name="regcommittee_getname")
     */
    public function getNameAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LanggasSisdikBundle:User')->find($id);

        if ($entity instanceof User) {
            $name = $entity->getName();
        } else {
            $name = $this->get('translator')->trans('label.username.undefined');
        }

        return new Response($name);
    }

    /**
     * @param Request $request
     * @Route("/ajax/ambilusername", name="regcommittee_ajax_get_username")
     */
    public function ajaxGetUsername(Request $request)
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()
            ->select('user')
            ->from('LanggasSisdikBundle:User', 'user')
            ->where('user.sekolah IS NOT NULL')
            ->andWhere('user.sekolah = :sekolah')
            ->andWhere('user.siswa IS NULL')
            ->orderBy('user.name', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($id != '') {
            $querybuilder->andWhere('user.id = :id');
            $querybuilder->setParameter('id', $id);
        } else {
            $querybuilder->andWhere('user.username LIKE ?1 OR user.name LIKE ?2');
            $querybuilder->setParameter(1, "%$filter%");
            $querybuilder->setParameter(2, "%$filter%");
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            if ($result instanceof User) {
                $retval[] = [
                    'source' => 'user', // user property of Personil
                    'target' => 'id', // id property of Personil
                    'id' => $result->getId(),
                    'label' => /** @Ignore */ $result->getName() . " ({$result->getUsername()})",
                    'value' => $result->getName(), // . $result->getId() . ':' . $result->getUsername(),
                ];
            }
        }

        if (count($retval) == 0) {
            $label = $this->get('translator')->trans("label.username.undefined");
            $retval[] = [
                'source' => 'user',
                'target' => 'id',
                'id' => $id,
                'label' => /** @Ignore */ $label,
                'value' => $label,
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id,
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.regcommittee', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
