<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\StatusKehadiranKepulanganSearchType;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\StatusKehadiranKepulangan;
use Fast\SisdikBundle\Form\StatusKehadiranKepulanganType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * StatusKehadiranKepulangan controller.
 *
 * @Route("/presence/status")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class StatusKehadiranKepulanganController extends Controller
{
    /**
     * route to list
     *
     * @Route("/", name="presence_status")
     * @Template()
     */
    public function indexAction() {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new StatusKehadiranKepulanganSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')->leftJoin('t.sekolah', 't2')
                ->orderBy('t2.nama', 'ASC')->addOrderBy('t.nama', 'ASC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchoption'] != '') {
                $querybuilder->andWhere("t.sekolah = :sekolah");
                $querybuilder->setParameter(':sekolah', $searchdata['searchoption']);
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('t.nama LIKE ?1');
                $querybuilder->setParameter(1, "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'form' => $searchform->createView(),
        );
    }

    /**
     * Finds and displays a StatusKehadiranKepulangan entity.
     *
     * @Route("/{id}/show", name="presence_status_show")
     * @Template()
     */
    public function showAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity StatusKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new StatusKehadiranKepulangan entity.
     *
     * @Route("/new", name="presence_status_new")
     * @Template()
     */
    public function newAction() {
        $this->setCurrentMenu();

        $entity = new StatusKehadiranKepulangan();
        $form = $this->createForm(new StatusKehadiranKepulanganType(), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new StatusKehadiranKepulangan entity.
     *
     * @Route("/create", name="presence_status_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:StatusKehadiranKepulangan:new.html.twig")
     */
    public function createAction(Request $request) {
        $this->setCurrentMenu();

        $entity = new StatusKehadiranKepulangan();
        $form = $this->createForm(new StatusKehadiranKepulanganType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.inserted',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.presencestatus');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_status_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing StatusKehadiranKepulangan entity.
     *
     * @Route("/{id}/edit", name="presence_status_edit")
     * @Template()
     */
    public function editAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity StatusKehadiranKepulangan tak ditemukan.');
        }

        $editForm = $this->createForm(new StatusKehadiranKepulanganType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing StatusKehadiranKepulangan entity.
     *
     * @Route("/{id}/update", name="presence_status_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:StatusKehadiranKepulangan:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity StatusKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new StatusKehadiranKepulanganType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.updated',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.presencestatus');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_status_edit',
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
     * Deletes a StatusKehadiranKepulangan entity.
     *
     * @Route("/{id}/delete", name="presence_status_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity StatusKehadiranKepulangan tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.deleted',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.presence.status.fail.delete',
                                            array(
                                                    '%name%' => $entity->getNama(),
                                                    '%school%' => $entity->getSekolah()->getNama()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('presence_status',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.pengaturan.sisdik']['links.presencestatus']->setCurrent(true);
    }
}
