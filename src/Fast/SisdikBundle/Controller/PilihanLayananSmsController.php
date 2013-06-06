<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Fast\SisdikBundle\Form\PilihanLayananSmsSearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Form\PilihanLayananSmsType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PilihanLayananSms controller.
 *
 * @Route("/layanansms")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class PilihanLayananSmsController extends Controller
{

    /**
     * Lists all PilihanLayananSms entities.
     *
     * @Route("/", name="layanansms")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new PilihanLayananSmsSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:PilihanLayananSms', 't')->leftJoin('t.sekolah', 't2')
                ->orderBy('t2.nama', 'ASC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder->where('t.sekolah = :sekolah');
                $querybuilder->setParameter("sekolah", $searchdata['sekolah']);
            }

            if ($searchdata['jenisLayanan'] != '') {
                $querybuilder->andWhere('t.jenisLayanan = :jenis');
                $querybuilder->setParameter("jenis", $searchdata['jenisLayanan']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'daftarJenisLayanan' => PilihanLayananSms::getDaftarLayanan()
        );
    }

    /**
     * Displays a form to create a new PilihanLayananSms entity.
     *
     * @Route("/new", name="layanansms_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction() {
        $this->setCurrentMenu();

        $entity = new PilihanLayananSms();
        $form = $this->createForm(new PilihanLayananSmsType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new PilihanLayananSms entity.
     *
     * @Route("/create", name="layanansms_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanLayananSms:new.html.twig")
     */
    public function createAction(Request $request) {
        $this->setCurrentMenu();

        $entity = new PilihanLayananSms();
        $form = $this->createForm(new PilihanLayananSmsType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.layanansms.tersimpan',
                                                array(
                                                    '%sekolah%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanansms');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('layanansms_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a PilihanLayananSms entity.
     *
     * @Route("/{id}", name="layanansms_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'daftarJenisLayanan' => PilihanLayananSms::getDaftarLayanan()
        );
    }

    /**
     * Displays a form to edit an existing PilihanLayananSms entity.
     *
     * @Route("/{id}/edit", name="layanansms_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $editForm = $this->createForm(new PilihanLayananSmsType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing PilihanLayananSms entity.
     *
     * @Route("/{id}/update", name="layanansms_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanLayananSms:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PilihanLayananSmsType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.layanansms.terbarui',
                                                array(
                                                    '%sekolah%' => $entity->getSekolah()->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanansms');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('layanansms_edit',
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
     * Deletes a PilihanLayananSms entity.
     *
     * @Route("/{id}/delete", name="layanansms_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:PilihanLayananSms')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.layanansms.terhapus',
                                            array(
                                                '%sekolah%' => $entity->getSekolah()->getNama()
                                            )));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.layanansms.gagal.dihapus',
                                            array(
                                                '%sekolah%' => $entity->getSekolah()->getNama()
                                            )));
        }

        return $this->redirect($this->generateUrl('layanansms'));
    }

    /**
     * Creates a form to delete a PilihanLayananSms entity by id.
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
        $menu['headings.pengaturan.sisdik']['links.layanansms']->setCurrent(true);
    }
}
