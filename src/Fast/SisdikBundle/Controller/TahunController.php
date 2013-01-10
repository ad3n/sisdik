<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Tahun;
use Fast\SisdikBundle\Form\TahunType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Tahun controller.
 *
 * @Route("/data/year")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class TahunController extends Controller
{
    /**
     * Lists all Tahun entities.
     *
     * @Route("/", name="data_year")
     * @Template()
     */
    public function indexAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('idsekolah', $idsekolah);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Activate a Tahun entity, and deactivate the rests.
     *
     * @Route("/{id}/activate", name="data_year_activate")
     */
    public function activateAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $query = $em->createQueryBuilder()->update('FastSisdikBundle:Tahun', 't')
                ->set('t.aktif', '0')->where('t.idsekolah = :idsekolah')
                ->setParameter('idsekolah', $idsekolah)->getQuery();
        $query->execute();

        $entity->setAktif(1);
        $em->persist($entity);
        $em->flush();

        return $this
                ->redirect(
                        $this
                                ->generateUrl('data_year',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    /**
     * Finds and displays a Tahun entity.
     *
     * @Route("/{id}/show", name="data_year_show")
     * @Template()
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Tahun entity.
     *
     * @Route("/new", name="data_year_new")
     * @Template()
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Tahun();
        $form = $this->createForm(new TahunType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Tahun entity.
     *
     * @Route("/create", name="data_year_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:Tahun:new.html.twig")
     */
    public function createAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Tahun();
        $form = $this->createForm(new TahunType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.year.inserted',
                                                array(
                                                    '%year%' => $entity->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_year_show',
                                                array(
                                                    'id' => $entity->getId()
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.year.school');
                throw new DBALException($exception);
            }
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Tahun entity.
     *
     * @Route("/{id}/edit", name="data_year_edit")
     * @Template()
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $editForm = $this->createForm(new TahunType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Tahun entity.
     *
     * @Route("/{id}/update", name="data_year_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Tahun:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new TahunType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.year.updated',
                                                array(
                                                    '%year%' => $entity->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_year_edit',
                                                array(
                                                        'id' => $id,
                                                        'page' => $this->getRequest()->get('page')
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.year.school');
                throw new DBALException($exception);
            }
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Tahun entity.
     *
     * @Route("/{id}/delete", name="data_year_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Tahun')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.year.deleted',
                                                array(
                                                    '%year%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.data.year.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('data_year',
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
        $menu['headings.setting']['links.year']->setCurrent(true);
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
