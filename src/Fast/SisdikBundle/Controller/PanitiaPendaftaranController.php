<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\Tahunmasuk;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Fast\SisdikBundle\Entity\User;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Fast\SisdikBundle\Form\PanitiaPendaftaranType;
use Fast\SisdikBundle\Entity\Personil;
use Fast\SisdikBundle\Form\SimpleTahunmasukSearchType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PanitiaPendaftaran controller.
 *
 * @Route("/regcommittee")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PanitiaPendaftaranController extends Controller
{
    /**
     * Lists all PanitiaPendaftaran entities.
     *
     * @Route("/", name="regcommittee")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SimpleTahunmasukSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:PanitiaPendaftaran', 't')->leftJoin('t.tahunmasuk', 't2')
                ->where('t.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC');
        $querybuilder->setParameter('sekolah', $sekolah->getId());

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :tahunmasuk');
                $querybuilder->setParameter('tahunmasuk', $searchdata['tahunmasuk']->getId());
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a PanitiaPendaftaran entity.
     *
     * @Route("/{id}/show", name="regcommittee_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Activate a panitia entity, and deactivate the rests.
     *
     * @Route("/{id}/activate", name="regcommittee_activate")
     */
    public function activateAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        $results = $em->getRepository('FastSisdikBundle:Tahunmasuk')
                ->findBy(array(
                    'sekolah' => $sekolah->getId()
                ));
        $daftarTahunmasuk = array();
        foreach ($results as $tahunmasuk) {
            if (is_object($tahunmasuk) && $tahunmasuk instanceof Tahunmasuk) {
                $daftarTahunmasuk[] = $tahunmasuk->getId();
            }
        }

        $query = $em->createQueryBuilder()->update('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->set('t.aktif', '0')->where('t.tahunmasuk IN (?1)')->setParameter(1, $daftarTahunmasuk)
                ->getQuery();
        $query->execute();

        $entity->setAktif(1);
        $entity->setDaftarPersonil($entity->getDaftarPersonil());
        $em->persist($entity);
        $em->flush();

        return $this
                ->redirect(
                        $this
                                ->generateUrl('regcommittee',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    /**
     * Displays a form to create a new PanitiaPendaftaran entity.
     *
     * @Route("/new", name="regcommittee_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PanitiaPendaftaran();

        $form = $this->createForm(new PanitiaPendaftaranType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new PanitiaPendaftaran entity.
     *
     * @Route("/create", name="regcommittee_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PanitiaPendaftaran:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PanitiaPendaftaran();
        $form = $this->createForm(new PanitiaPendaftaranType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);

                // give user the necessary role
                $userManager = $this->container->get('fos_user.user_manager');
                $daftarPersonil = $entity->getDaftarPersonil();
                if ($daftarPersonil instanceof ArrayCollection) {
                    foreach ($daftarPersonil as $personil) {
                        if ($personil instanceof Personil) {
                            if ($personil->getId() !== NULL) {
                                $user = $userManager
                                        ->findUserBy(
                                                array(
                                                    'id' => $personil->getId()
                                                ));

                                $user->addRole('ROLE_PANITIA_PSB');
                                $userManager->updateUser($user);
                            }
                        }
                    }
                }

                $user = $userManager
                        ->findUserBy(
                                array(
                                    'id' => $entity->getKetuaPanitia()->getId()
                                ));
                $user->addRole('ROLE_KETUA_PANITIA_PSB');
                $userManager->updateUser($user);

                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.registration.committee.inserted',
                                                array(
                                                    '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')
                        ->trans('exception.unique.registration.committee',
                                array(
                                    '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                ));
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('regcommittee_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing PanitiaPendaftaran entity.
     *
     * @Route("/{id}/edit", name="regcommittee_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm(new PanitiaPendaftaranType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing PanitiaPendaftaran entity.
     *
     * @Route("/{id}/update", name="regcommittee_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PanitiaPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        $entity->setPanitia(''); // prevent form generation read from database

        $editForm = $this->createForm(new PanitiaPendaftaranType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);

                // give user the necessary role
                $userManager = $this->container->get('fos_user.user_manager');
                $daftarPersonil = $entity->getDaftarPersonil();
                if ($daftarPersonil instanceof ArrayCollection) {
                    foreach ($daftarPersonil as $personil) {
                        if ($personil instanceof Personil) {
                            if ($personil->getId() !== NULL) {
                                $user = $userManager
                                        ->findUserBy(
                                                array(
                                                    'id' => $personil->getId()
                                                ));

                                $user->addRole('ROLE_PANITIA_PSB');
                                $userManager->updateUser($user);
                            }
                        }
                    }
                }

                $user = $userManager
                        ->findUserBy(
                                array(
                                    'id' => $entity->getKetuaPanitia()->getId()
                                ));
                $user->addRole('ROLE_KETUA_PANITIA_PSB');
                $userManager->updateUser($user);

                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.registration.committee.updated',
                                                array(
                                                    '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')
                        ->trans('exception.unique.registration.committee',
                                array(
                                    '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                ));
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('regcommittee_edit',
                                            array(
                                                'id' => $id, 'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a PanitiaPendaftaran entity.
     *
     * @Route("/{id}/delete", name="regcommittee_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PanitiaPendaftaran tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.registration.committee.deleted',
                                            array(
                                                '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                            )));
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')
                                    ->trans('flash.registration.committee.fail.delete',
                                            array(
                                                '%yearentry%' => $entity->getTahunmasuk()->getTahun()
                                            )));
        }

        return $this->redirect($this->generateUrl('regcommittee'));
    }

    /**
     * Finds a name of a username
     *
     * @Route("/name/{id}", name="regcommittee_getname")
     */
    public function getNameAction($id) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('FastSisdikBundle:User')->find($id);

        if ($entity instanceof User) {
            $name = $entity->getName();
        } else {
            $name = $this->get('translator')->trans('label.username.undefined');
        }

        return new Response($name);
    }

    /**
     * Get username through autocomplete box
     *
     * @param Request $request
     * @Route("/ajax/filterstudent", name="regcommittee_ajax_get_username")
     */
    public function ajaxGetUsername(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');

        $query = $em
                ->createQuery(
                        "SELECT t FROM FastSisdikBundle:User t "
                                . " WHERE t.sekolah IS NOT NULL AND t.sekolah = :sekolah "
                                . " AND (t.username LIKE :filter OR t.name LIKE :filter) "
                                . " AND t.siswa IS NULL");
        $query->setParameter("sekolah", $sekolah->getId());
        $query->setParameter('filter', "%$filter%");
        $results = $query->getResult();

        $retval = array();
        foreach ($results as $result) {
            if ($result instanceof User) {
                $retval[] = array(
                        'source' => 'user', // user property of Personil
                        'target' => 'id', // id property of Personil
                        'id' => $result->getId(),
                        'label' => $result->getName() . " ({$result->getUsername()})",
                        'value' => $result->getName(), // . $result->getId() . ':' . $result->getUsername(),
                );
            }
        }

        return new Response(json_encode($retval), 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.regcommittee']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
