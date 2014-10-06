<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/wali-kelas")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class WaliKelasController extends Controller
{
    /**
     * @Route("/", name="data_classguardian")
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
            ->leftJoin('waliKelas.tahunAkademik', 'tahunAkademik')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('tahunAkademik.urutan', 'DESC')
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
                    ->andWhere("waliKelas.nama LIKE :searchkey")
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
     * @Route("/{id}/show", name="data_classguardian_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="data_classguardian_new")
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
     * @Route("/create", name="data_classguardian_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:WaliKelas:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new WaliKelas();
        $form = $this->createForm('sisdik_walikelas', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.classguardian.inserted', [
                        '%classguardian%' => $entity->getNama()
                    ]))
                ;

                return $this->redirect($this->generateUrl('data_classguardian_show', [
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
     * @Route("/{id}/edit", name="data_classguardian_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
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
     * @Route("/{id}/update", name="data_classguardian_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:WaliKelas:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_walikelas', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.classguardian.updated', [
                        '%classguardian%' => $entity->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('data_classguardian_edit', [
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
     * @Route("/{id}/delete", name="data_classguardian_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.classguardian.deleted', [
                    '%classguardian%' => $entity->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.data.classguardian.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('data_classguardian'));
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
