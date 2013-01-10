<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Penjurusan;
use Fast\SisdikBundle\Form\PenjurusanType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Penjurusan controller.
 *
 * @Route("/placement")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class PenjurusanController extends Controller
{
    /**
     * Lists all Penjurusan entities.
     *
     * @Route("/", name="settings_placement")
     * @Template()
     */
    public function indexAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $results = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Penjurusan', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.root ASC, t.lft', 'ASC')->setParameter('idsekolah', $idsekolah);
        }

        $repo = $em->getRepository("FastSisdikBundle:Penjurusan");

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($results, $this->getRequest()->query->get('page', 1), 20);

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/move-up/{id}", name="settings_placement_moveup")
     */
    public function moveUpAction($id) {
        $repo = $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Penjurusan');
        $node = $repo->find($id);

        $repo->moveUp($node);
        
        return $this->redirect($this->generateUrl('settings_placement'));
    }

    /**
     * @Route("/move-down/{id}", name="settings_placement_movedown")
     */
    public function moveDownAction($id) {
        $repo = $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Penjurusan');
        $node = $repo->find($id);

        $repo->moveDown($node);
        
        return $this->redirect($this->generateUrl('settings_placement'));
    }

    /**
     * Finds and displays a Penjurusan entity.
     *
     * @Route("/{id}/show", name="settings_placement_show")
     * @Template()
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Penjurusan entity.
     *
     * @Route("/new", name="settings_placement_new")
     * @Template()
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Penjurusan();
        $form = $this->createForm(new PenjurusanType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Penjurusan entity.
     *
     * @Route("/create", name="settings_placement_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:Penjurusan:new.html.twig")
     */
    public function createAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new Penjurusan();
        $form = $this->createForm(new PenjurusanType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.settings.placement.inserted',
                                            array(
                                                '%node%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_placement_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Penjurusan entity.
     *
     * @Route("/{id}/edit", name="settings_placement_edit")
     * @Template()
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $editForm = $this->createForm(new PenjurusanType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Penjurusan entity.
     *
     * @Route("/{id}/update", name="settings_placement_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Penjurusan:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Penjurusan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PenjurusanType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.settings.placement.updated',
                                            array(
                                                '%node%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_placement_edit',
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
     * Deletes a Penjurusan entity.
     *
     * @Route("/{id}/delete", name="settings_placement_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Penjurusan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.placement.deleted',
                                                array(
                                                    '%node%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.settings.placement.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('settings_placement',
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
        $menu['headings.setting']['links.placement']->setCurrent(true);
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
