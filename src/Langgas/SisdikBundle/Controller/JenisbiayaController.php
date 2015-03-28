<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Jenisbiaya;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/jenis-biaya")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class JenisbiayaController extends Controller
{
    /**
     * @Route("/", name="fee_type")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('jenisbiaya')
            ->from('LanggasSisdikBundle:Jenisbiaya', 'jenisbiaya')
            ->where('jenisbiaya.sekolah = :sekolah')
            ->orderBy('jenisbiaya.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{id}/show", name="fee_type_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="fee_type_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Jenisbiaya();
        $form = $this->createForm('sisdik_jenisbiaya', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="fee_type_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Jenisbiaya:new.html.twig")
     */
    public function createAction()
    {
        $this->setCurrentMenu();

        $entity = new Jenisbiaya();

        $form = $this->createForm('sisdik_jenisbiaya', $entity);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.fee.type.inserted', [
                    '%feetype%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('fee_type_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="fee_type_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_jenisbiaya', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="fee_type_update")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Jenisbiaya:edit.html.twig")
     */
    public function updateAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_jenisbiaya', $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.fee.type.updated', [
                    '%feetype%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('fee_type_edit', [
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
     * @Route("/{id}/delete", name="fee_type_delete")
     * @Method("post")
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Jenisbiaya')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
            }

            if ($this->get('security.authorization_checker')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.type.deleted', [
                        '%feetype%' => $entity->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.fee.type.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('fee_type'));
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.fee.type', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
