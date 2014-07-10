<?php

namespace Langgas\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Langgas\SisdikBundle\Form\MesinKehadiranType;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * MesinKehadiran controller.
 *
 * @Route("/attendancemachine")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class MesinKehadiranController extends Controller
{
    /**
     * Lists all MesinKehadiran entities.
     *
     * @Route("/", name="attendancemachine")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('LanggasSisdikBundle:MesinKehadiran', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.alamatIp', 'ASC')->setParameter('sekolah', $sekolah->getId());
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Finds and displays a MesinKehadiran entity.
     *
     * @Route("/{id}/show", name="attendancemachine_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new MesinKehadiran entity.
     *
     * @Route("/new", name="attendancemachine_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new MesinKehadiran();
        $form = $this->createForm(new MesinKehadiranType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new MesinKehadiran entity.
     *
     * @Route("/create", name="attendancemachine_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:MesinKehadiran:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new MesinKehadiran();
        $form = $this->createForm(new MesinKehadiranType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.attendancemachine.inserted',
                                            array(
                                                '%ip%' => $entity->getAlamatIp()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('attendancemachine_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing MesinKehadiran entity.
     *
     * @Route("/{id}/edit", name="attendancemachine_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        $editForm = $this->createForm(new MesinKehadiranType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing MesinKehadiran entity.
     *
     * @Route("/{id}/update", name="attendancemachine_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:MesinKehadiran:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new MesinKehadiranType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.attendancemachine.updated',
                                            array(
                                                '%ip%' => $entity->getAlamatIp()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('attendancemachine_edit',
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
     * Deletes a MesinKehadiran entity.
     *
     * @Route("/{id}/delete", name="attendancemachine_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.attendancemachine.deleted',
                                            array(
                                                '%ip%' => $entity->getAlamatIp()
                                            )));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.attendancemachine.fail.delete',
                                            array(
                                                '%ip%' => $entity->getAlamatIp()
                                            )));
        }

        return $this->redirect($this->generateUrl('attendancemachine'));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', array(), 'navigations')][$this->get('translator')->trans('links.attendancemachine', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
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
