<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\StatusKehadiranKepulangan;
use Fast\SisdikBundle\Form\StatusKehadiranKepulanganType;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Controller\SekolahList;
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
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('presence_status_list'));
    }

    /**
     * Lists all StatusKehadiranKepulangan entities, filtered by school
     * 
     * @Route("/list/{filter}", name="presence_status_list", defaults={"filter"="all"})
     * @Template()
     */
    public function listAction($filter) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($filter == 'all') {
            $query = $em
                    ->createQuery(
                            "SELECT t FROM FastSisdikBundle:StatusKehadiranKepulangan t
                            JOIN t.sekolah t1 
                            ORDER BY t1.nama ASC, t.nama ASC");
        } else {
            $query = $em
                    ->createQuery(
                            "SELECT t FROM FastSisdikBundle:StatusKehadiranKepulangan t
                            JOIN t.sekolah t1
                            WHERE t.sekolah = '$filter'
                            ORDER BY t.nama ASC");
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $this->getRequest()->query->get('page', 1));

        $sekolahlist = new SekolahList($this->container);
        return array(
                'pagination' => $pagination,
                'schools' => $sekolahlist->buildSekolahStatusKehadiranKepulanganList(), 'filter' => $filter,
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

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.inserted',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()
                                                                ->getNama()
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

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.updated',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()
                                                                ->getNama()
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
                                                    'id' => $id,
                                                    'page' => $this->getRequest()->get('page')
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

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.presence.status.deleted',
                                                array(
                                                        '%name%' => $entity->getNama(),
                                                        '%school%' => $entity->getSekolah()
                                                                ->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')
                                    ->trans('flash.presence.status.fail.delete',
                                            array(
                                                    '%name%' => $entity->getNama(),
                                                    '%school%' => $entity->getSekolah()
                                                            ->getNama()
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
        $menu['headings.presence']['links.presencestatus']->setCurrent(true);
    }
}
