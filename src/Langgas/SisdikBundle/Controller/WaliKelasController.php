<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use FOS\UserBundle\Doctrine\UserManager;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/wali-kelas")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class WaliKelasController extends Controller
{
    /**
     * @Route("/", name="walikelas")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caritahunakademik');

        $querybuilder = $em->createQueryBuilder()
            ->select('waliKelas')
            ->from('LanggasSisdikBundle:WaliKelas', 'waliKelas')
            ->leftJoin('waliKelas.kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->leftJoin('waliKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('waliKelas.user', 'user')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder
                    ->andWhere('waliKelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("user.name LIKE :searchkey OR user.username LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;
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
     * @Route("/{id}/show", name="walikelas_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity instanceof WaliKelas) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
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
     * @Route("/new", name="walikelas_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new WaliKelas();
        $form = $this->createForm('sisdik_walikelas', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="walikelas_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:WaliKelas:new.html.twig")
     */
    public function createAction()
    {
        $this->setCurrentMenu();

        $entity = new WaliKelas();
        $form = $this->createForm('sisdik_walikelas', $entity);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                /* @var $userManager UserManager */
                $userManager = $this->container->get('fos_user.user_manager');
                $user = $userManager->findUserBy([
                    'id' => $entity->getUser()->getId(),
                ]);

                if ($user instanceof User) {
                    if (!$user->hasRole('ROLE_SISWA')) {
                        $user->addRole('ROLE_WALI_KELAS');
                        $userManager->updateUser($user);
                    }
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.classguardian.inserted', [
                        '%tahun-akademik%' => $entity->getTahunAkademik()->getNama(),
                        '%kelas%' => $entity->getKelas()->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('walikelas_show', [
                    'id' => $entity->getId(),
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.classguardian');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="walikelas_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity instanceof WaliKelas) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_walikelas', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="walikelas_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:WaliKelas:edit.html.twig")
     */
    public function updateAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity instanceof WaliKelas) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_walikelas', $entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $userManager = $this->container->get('fos_user.user_manager');
                $user = $userManager->findUserBy([
                    'id' => $entity->getUser()->getId(),
                ]);

                if ($user instanceof User) {
                    $user->addRole('ROLE_WALI_KELAS');
                    $userManager->updateUser($user);
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.classguardian.updated', [
                        '%tahun-akademik%' => $entity->getTahunAkademik()->getNama(),
                        '%kelas%' => $entity->getKelas()->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('walikelas_edit', [
                    'id' => $id,
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.classguardian');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="walikelas_delete")
     * @Method("POST")
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
            }

            if ($this->get('security.context')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.classguardian.deleted', [
                    '%tahun-akademik%' => $entity->getTahunAkademik()->getNama(),
                    '%kelas%' => $entity->getKelas()->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.data.classguardian.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('walikelas'));
    }

    /**
     * @Route("/ajax/ambiluser", name="walikelas_ambiluser")
     * @Method("GET")
     */
    public function ajaxGetWaliKelasAction()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()
            ->select('user')
            ->from('LanggasSisdikBundle:User', 'user')
            ->where('user.sekolah = :sekolah')
            ->andWhere('user.siswa IS NULL')
            //->andWhere("REGEXP('^[0-9]+$', user.username) = false")
            ->orderBy('user.name', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($id != '') {
            $querybuilder
                ->andWhere('user.id = :id')
                ->setParameter('id', $id)
            ;
        } else {
            $querybuilder
                ->andWhere('user.name LIKE :filter OR user.username LIKE :filter')
                ->setParameter('filter', "%$filter%")
            ;
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            if ($result instanceof User) {
                $retval[] = [
                    'id' => $result->getId(),
                    'label' =>/** @Ignore */ $result->getName().' ('.$result->getUsername().')',
                    'value' => $result->getName(),
                ];
            }
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
        $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.data.classguardian', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
