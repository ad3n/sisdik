<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PilihanKirimSms;
use Fast\SisdikBundle\Form\PilihanKirimSmsType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PilihanKirimSms controller.
 *
 * @Route("/sendmessageoptions")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class PilihanKirimSmsController extends Controller
{

    /**
     * Lists all PilihanKirimSms entities.
     *
     * @Route("/", name="sendmessageoptions")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SimpleSearchFormType());

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PilihanKirimSms', 't')
                ->leftJoin('t.sekolah', 't2')->orderBy('t2.nama', 'ASC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder->where('t2.nama LIKE ?1');
                $querybuilder->setParameter(1, "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView(),
        );
    }

    /**
     * Displays a form to create a new PilihanKirimSms entity.
     *
     * @Route("/new", name="sendmessageoptions_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction() {
        $this->setCurrentMenu();

        $entity = new PilihanKirimSms();
        $form = $this->createForm(new PilihanKirimSmsType($this->container, 'new'), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new PilihanKirimSms entity.
     *
     * @Route("/", name="sendmessageoptions_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanKirimSms:new.html.twig")
     */
    public function createAction(Request $request) {
        $this->setCurrentMenu();

        $entity = new PilihanKirimSms();
        $form = $this->createForm(new PilihanKirimSmsType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.sendmessageoptions.inserted',
                                                array(
                                                    '%school%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.sendmessageoptions');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('sendmessageoptions_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a PilihanKirimSms entity.
     *
     * @Route("/{id}", name="sendmessageoptions_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanKirimSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanKirimSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing PilihanKirimSms entity.
     *
     * @Route("/{id}/edit", name="sendmessageoptions_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanKirimSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanKirimSms tak ditemukan.');
        }

        $editForm = $this->createForm(new PilihanKirimSmsType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing PilihanKirimSms entity.
     *
     * @Route("/{id}", name="sendmessageoptions_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanKirimSms:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanKirimSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanKirimSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PilihanKirimSmsType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.sendmessageoptions.updated',
                                                array(
                                                    '%school%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.sendmessageoptions');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('sendmessageoptions_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a PilihanKirimSms entity.
     *
     * @Route("/{id}/delete", name="sendmessageoptions_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:PilihanKirimSms')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PilihanKirimSms tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.sendmessageoptions.deleted',
                                            array(
                                                '%school%' => $entity->getSekolah()->getNama()
                                            )));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.sendmessageoptions.fail.delete',
                                            array(
                                                '%school%' => $entity->getSekolah()->getNama()
                                            )));
        }

        return $this->redirect($this->generateUrl('sendmessageoptions'));
    }

    /**
     * Creates a form to delete a PilihanKirimSms entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.setting']['links.sendmessageoptions']->setCurrent(true);
    }
}
