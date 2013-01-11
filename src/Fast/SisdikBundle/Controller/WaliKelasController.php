<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\WaliKelas;
use Fast\SisdikBundle\Form\WaliKelasType;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Entity\Tahun;
use Fast\SisdikBundle\Form\WaliKelasSearchType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * WaliKelas controller.
 *
 * @Route("/data/classguardian")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class WaliKelasController extends Controller
{
    /**
     * Lists all WaliKelas entities.
     *
     * @Route("/", name="data_classguardian")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new WaliKelasSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:WaliKelas', 't')->leftJoin('t.kelas', 't2')
                ->leftJoin('t.tahun', 't3')->where('t2.sekolah = :sekolah')
                ->orderBy('t3.urutan', 'DESC')->addOrderBy('t2.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah);

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']);
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere("t.nama LIKE :searchkey");
                $querybuilder->setParameter('searchkey', '%' . $searchdata['searchkey'] . '%');
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a WaliKelas entity.
     *
     * @Route("/{id}/show", name="data_classguardian_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new WaliKelas entity.
     *
     * @Route("/new", name="data_classguardian_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new WaliKelas();
        $form = $this->createForm(new WaliKelasType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new WaliKelas entity.
     *
     * @Route("/create", name="data_classguardian_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:WaliKelas:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new WaliKelas();
        $form = $this->createForm(new WaliKelasType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.classguardian.inserted',
                                                array(
                                                    '%classguardian%' => $entity->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_classguardian_show',
                                                array(
                                                    'id' => $entity->getId()
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.classguardian');
                throw new DBALException($exception);
            }
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing WaliKelas entity.
     *
     * @Route("/{id}/edit", name="data_classguardian_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        $editForm = $this->createForm(new WaliKelasType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing WaliKelas entity.
     *
     * @Route("/{id}/update", name="data_classguardian_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:WaliKelas:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:WaliKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new WaliKelasType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.classguardian.updated',
                                                array(
                                                    '%classguardian%' => $entity->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_classguardian_edit',
                                                array(
                                                        'id' => $id,
                                                        'page' => $this->getRequest()->get('page')
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.classguardian');
                throw new DBALException($exception);
            }
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a WaliKelas entity.
     *
     * @Route("/{id}/delete", name="data_classguardian_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:WaliKelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity WaliKelas tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.data.classguardian.deleted',
                                            array(
                                                '%classguardian%' => $entity->getNama()
                                            )));
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.data.classguardian.fail.delete'));
        }

        return $this->redirect($this->generateUrl('data_classguardian'));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.data.academic']['links.data.classguardian']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
