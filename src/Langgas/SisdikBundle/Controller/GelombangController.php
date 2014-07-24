<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/gelombang-penerimaan")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN')")
 */
class GelombangController extends Controller
{
    /**
     * @Route("/", name="settings_admissiongroup")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('gelombang')
            ->from('LanggasSisdikBundle:Gelombang', 'gelombang')
            ->where('gelombang.sekolah = :sekolah')
            ->orderBy('gelombang.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{id}/show", name="settings_admissiongroup_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Gelombang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Gelombang tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="settings_admissiongroup_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Gelombang;
        $form = $this->createForm('sisdik_gelombang', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="settings_admissiongroup_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Gelombang:new.html.twig")
     */
    public function createAction()
    {
        $this->setCurrentMenu();

        $entity = new Gelombang;

        $form = $this->createForm('sisdik_gelombang', $entity);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.settings.admissiongroup.inserted', [
                    '%admissiongroup%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('settings_admissiongroup_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="settings_admissiongroup_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Gelombang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Gelombang tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_gelombang', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="settings_admissiongroup_update")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Gelombang:edit.html.twig")
     */
    public function updateAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Gelombang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Gelombang tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_gelombang', $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.settings.admissiongroup.updated', [
                    '%admissiongroup%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('settings_admissiongroup_edit', [
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
     * @Route("/{id}/delete", name="settings_admissiongroup_delete")
     * @Method("post")
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Gelombang')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Gelombang tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.admissiongroup.deleted', [
                        '%admissiongroup%' => $entity->getNama(),
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
                ->add('error', $this->get('translator')->trans('flash.settings.admissiongroup.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('settings_admissiongroup'));
    }

    /**
     * @param  integer                      $id
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.setting', array(), 'navigations')][$translator->trans('links.admissiongroup', array(), 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
