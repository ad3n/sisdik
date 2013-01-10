<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Jenisbiaya;
use Fast\SisdikBundle\Form\JenisbiayaType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Jenisbiaya controller.
 *
 * @Route("/fee/type")
 */
class JenisbiayaController extends Controller
{
    /**
     * Lists all Jenisbiaya entities.
     *
     * @Route("/", name="fee_type")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Jenisbiaya', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.nama', 'ASC')->setParameter('idsekolah', $idsekolah);
        } else {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Jenisbiaya', 't')->orderBy('t.nama', 'ASC');
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Finds and displays a Jenisbiaya entity.
     *
     * @Route("/{id}/show", name="fee_type_show")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Jenisbiaya entity.
     *
     * @Route("/new", name="fee_type_new")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Jenisbiaya();
        $form = $this->createForm(new JenisbiayaType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Creates a new Jenisbiaya entity.
     *
     * @Route("/create", name="fee_type_create")
     * @Method("post")
     * @Template("FastSisdikBundle:Jenisbiaya:new.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function createAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Jenisbiaya();
        $request = $this->getRequest();
        $form = $this->createForm(new JenisbiayaType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.fee.type.inserted',
                                            array(
                                                '%feetype%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_type_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));

        }

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing Jenisbiaya entity.
     *
     * @Route("/{id}/edit", name="fee_type_edit")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        $editForm = $this->createForm(new JenisbiayaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Jenisbiaya entity.
     *
     * @Route("/{id}/update", name="fee_type_update")
     * @Method("post")
     * @Template("FastSisdikBundle:Jenisbiaya:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function updateAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Jenisbiaya')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
        }

        $editForm = $this->createForm(new JenisbiayaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.fee.type.updated',
                                            array(
                                                '%feetype%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_type_edit',
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
     * Deletes a Jenisbiaya entity.
     *
     * @Route("/{id}/delete", name="fee_type_delete")
     * @Method("post")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function deleteAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Jenisbiaya')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Jenisbiaya tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.fee.type.deleted',
                                                array(
                                                    '%feetype%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.fee.type.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('fee_type',
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
        $menu['headings.fee']['links.fee.type']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            return $idsekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
