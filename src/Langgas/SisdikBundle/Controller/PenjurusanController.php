<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Penjurusan;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/penjurusan")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PenjurusanController extends Controller
{
    /**
     * @Route("/", name="settings_placement")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('penjurusan')
            ->from('LanggasSisdikBundle:Penjurusan', 'penjurusan')
            ->where('penjurusan.sekolah = :sekolah')
            ->orderBy('penjurusan.root', 'ASC')
            ->addOrderBy('penjurusan.lft', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 20);

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/move-up/{id}", name="settings_placement_moveup")
     */
    public function moveUpAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('LanggasSisdikBundle:Penjurusan');
        $node = $repo->find($id);
        $repo->moveUp($node);

        return $this->redirect($this->generateUrl('settings_placement'));
    }

    /**
     * @Route("/move-down/{id}", name="settings_placement_movedown")
     */
    public function moveDownAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('LanggasSisdikBundle:Penjurusan');
        $node = $repo->find($id);
        $repo->moveDown($node);

        return $this->redirect($this->generateUrl('settings_placement'));
    }

    /**
     * @Route("/{id}/show", name="settings_placement_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="settings_placement_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Penjurusan();
        $form = $this->createForm('sisdik_penjurusan', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="settings_placement_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Penjurusan:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new Penjurusan();
        $form = $this->createForm('sisdik_penjurusan', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.settings.placement.inserted', [
                    '%node%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('settings_placement_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="settings_placement_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_penjurusan', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="settings_placement_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Penjurusan:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_penjurusan', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.settings.placement.updated', [
                    '%node%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('settings_placement_edit', [
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
     * @Route("/{id}/delete", name="settings_placement_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->getSekolah();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Penjurusan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.settings.placement.deleted',
                                                array(
                                                    '%node%' => $entity->getNama(),
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.settings.placement.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('settings_placement'));
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
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.placement', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
