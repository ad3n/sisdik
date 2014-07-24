<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\BiayaSekali;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/biaya-sekali")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA')")
 */
class BiayaSekaliController extends Controller
{
    /**
     * @Route("/", name="fee_once")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caribiayasekali');

        $querybuilder = $em->createQueryBuilder()
            ->select('biayaSekali')
            ->from('LanggasSisdikBundle:BiayaSekali', 'biayaSekali')
            ->leftJoin('biayaSekali.tahun', 'tahun')
            ->leftJoin('biayaSekali.jenisbiaya', 'jenisbiaya')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('biayaSekali.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('biayaSekali.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']);
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder->andWhere("(jenisbiaya.nama LIKE :jenisbiaya OR jenisbiaya.kode = :kodejenisbiaya)");
                $querybuilder->setParameter('jenisbiaya', "%{$searchdata['jenisbiaya']}%");
                $querybuilder->setParameter('kodejenisbiaya', $searchdata['jenisbiaya']);
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
     * @Route("/{id}/show", name="fee_once_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="fee_once_new")
     * @Template()
     */
    public function newAction()
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $entity = new BiayaSekali;
        $form = $this->createForm('sisdik_biayasekali', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="fee_once_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:BiayaSekali:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $entity = new BiayaSekali;
        $form = $this->createForm('sisdik_biayasekali', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.once.inserted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_once_show', [
                'id' => $entity->getId(),
            ]));

        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="fee_once_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        if ($entity->isTerpakai() === true) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('info', $this->get('translator')->trans('flash.fee.once.update.restriction'))
            ;
        }

        $editForm = $this->createForm('sisdik_biayasekali', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="fee_once_update")
     * @Method("post")
     * @Template("LanggasSisdikBundle:BiayaSekali:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_biayasekali', $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.once.updated'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_once_edit', [
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
     * @Route("/{id}/delete", name="fee_once_delete")
     * @Method("post")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->getSekolah();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.oncefee');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.once.deleted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.fee.once.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('fee_once'));
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
        $menu[$translator->trans('headings.fee', array(), 'navigations')][$translator->trans('links.fee.once', array(), 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
