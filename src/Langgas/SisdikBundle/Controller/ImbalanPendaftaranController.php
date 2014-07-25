<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\ImbalanPendaftaran;
use Langgas\SisdikBundle\Entity\JenisImbalan;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/imbalan-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class ImbalanPendaftaranController extends Controller
{
    /**
     * @Route("/", name="rewardamount")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caritahun');

        $querybuilder = $em->createQueryBuilder()
            ->select('imbalanPendaftaran')
            ->from('LanggasSisdikBundle:ImbalanPendaftaran', 'imbalanPendaftaran')
            ->leftJoin('imbalanPendaftaran.tahun', 'tahun')
            ->leftJoin('imbalanPendaftaran.gelombang', 'gelombang')
            ->leftJoin('imbalanPendaftaran.jenisImbalan', 't4')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'ASC')
        ;
        $querybuilder->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder->andWhere('imbalanPendaftaran.tahun = :tahun');
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
     * @Route("/{id}/show", name="rewardamount_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity ImbalanPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="rewardamount_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new ImbalanPendaftaran;

        $form = $this->createForm('sisdik_imbalanpendaftaran', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="rewardamount_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:ImbalanPendaftaran:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new ImbalanPendaftaran;

        $form = $this->createForm('sisdik_imbalanpendaftaran', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.amount.inserted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardamount');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('rewardamount_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="rewardamount_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity ImbalanPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_imbalanpendaftaran', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="rewardamount_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:ImbalanPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity ImbalanPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_imbalanpendaftaran', $entity);

        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.amount.updated'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardamount');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('rewardamount_edit', [
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
     * @Route("/{id}/delete", name="rewardamount_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:ImbalanPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity ImbalanPendaftaran tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.amount.deleted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.reward.amount.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('rewardamount'));
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.reward.amount', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
