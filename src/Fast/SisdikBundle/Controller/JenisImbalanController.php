<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\JenisImbalan;
use Fast\SisdikBundle\Form\JenisImbalanType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * JenisImbalan controller.
 *
 * @Route("/rewardtype")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class JenisImbalanController extends Controller
{
    /**
     * Lists all JenisImbalan entities.
     *
     * @Route("/", name="rewardtype")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:JenisImbalan', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.nama', 'ASC')->setParameter('sekolah', $sekolah->getId());
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Finds and displays a JenisImbalan entity.
     *
     * @Route("/{id}/show", name="rewardtype_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new JenisImbalan entity.
     *
     * @Route("/new", name="rewardtype_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JenisImbalan();
        $form = $this->createForm(new JenisImbalanType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new JenisImbalan entity.
     *
     * @Route("/create", name="rewardtype_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisImbalan:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JenisImbalan();
        $form = $this->createForm(new JenisImbalanType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.reward.type.inserted',
                                                array(
                                                    '%rewardtype%' => $entity->getNama()
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardtype');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('rewardtype_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing JenisImbalan entity.
     *
     * @Route("/{id}/edit", name="rewardtype_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
        }

        $editForm = $this->createForm(new JenisImbalanType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing JenisImbalan entity.
     *
     * @Route("/{id}/update", name="rewardtype_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisImbalan:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JenisImbalanType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.reward.type.updated',
                                                array(
                                                    '%rewardtype%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.rewardtype');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('rewardtype_edit',
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
     * Deletes a JenisImbalan entity.
     *
     * @Route("/{id}/delete", name="rewardtype_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JenisImbalan tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.reward.type.deleted',
                                                array(
                                                    '%rewardtype%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        }

        return $this->redirect($this->generateUrl('rewardtype'));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.fee']['links.reward.type']->setCurrent(true);
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
