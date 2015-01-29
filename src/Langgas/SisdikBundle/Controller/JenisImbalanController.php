<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\JenisImbalan;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/jenis-imbalan")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class JenisImbalanController extends Controller
{
    /**
     * @Route("/", name="rewardtype")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('jenisImbalan')
            ->from('LanggasSisdikBundle:JenisImbalan', 'jenisImbalan')
            ->where('jenisImbalan.sekolah = :sekolah')
            ->orderBy('jenisImbalan.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{id}/show", name="rewardtype_show")
     * @Template()
     */
    public function showAction($id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
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
     * @Route("/new", name="rewardtype_new")
     * @Template()
     */
    public function newAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $entity = new JenisImbalan;
        $form = $this->createForm('sisdik_jenisimbalan', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="rewardtype_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JenisImbalan:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $entity = new JenisImbalan;
        $form = $this->createForm('sisdik_jenisimbalan', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.type.inserted', [
                        '%rewardtype%' => $entity->getNama(),
                    ]))
                ;

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardtype');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('rewardtype_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="rewardtype_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_jenisimbalan', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="rewardtype_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JenisImbalan:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_jenisimbalan', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.type.updated', [
                        '%rewardtype%' => $entity->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardtype');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('rewardtype_edit', [
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
     * @Route("/{id}/delete", name="rewardtype_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->getSekolah();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:JenisImbalan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
            }

            if ($this->get('security.context')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.reward.type.deleted', [
                        '%rewardtype%' => $entity->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        }

        return $this->redirect($this->generateUrl('rewardtype'));
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.reward.type', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
