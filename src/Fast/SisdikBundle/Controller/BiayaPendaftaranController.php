<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\BiayaPendaftaranType;
use Fast\SisdikBundle\Form\BiayaSearchFormType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * BiayaPendaftaran controller.
 *
 * @Route("/fee/registration")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA')")
 */
class BiayaPendaftaranController extends Controller
{
    /**
     * Lists all BiayaPendaftaran entities.
     *
     * @Route("/", name="fee_registration")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't')->leftJoin('t.tahunmasuk', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.jenisbiaya', 't4')
                ->where('t2.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->orderBy('t2.tahun', 'DESC')->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.urutan', 'ASC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunmasuk'] != '') {
                $querybuilder->andWhere('t.tahunmasuk = :tahunmasuk');
                $querybuilder->setParameter('tahunmasuk', $searchdata['tahunmasuk']->getId());
            }
            if ($searchdata['gelombang'] != '') {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder->andWhere("(t4.nama LIKE :jenisbiaya OR t4.kode = :kodejenisbiaya)");
                $querybuilder->setParameter('jenisbiaya', "%{$searchdata['jenisbiaya']}%");
                $querybuilder->setParameter('kodejenisbiaya', $searchdata['jenisbiaya']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a BiayaPendaftaran entity.
     *
     * @Route("/{id}/show", name="fee_registration_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new BiayaPendaftaran entity.
     *
     * @Route("/new", name="fee_registration_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new BiayaPendaftaran entity.
     *
     * @Route("/create", name="fee_registration_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:BiayaPendaftaran:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')->trans('flash.fee.registration.inserted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_registration_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/edit", name="fee_registration_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/update", name="fee_registration_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:BiayaPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success', $this->get('translator')->trans('flash.fee.registration.updated'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_registration_edit',
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
     * Deletes a BiayaPendaftaran entity.
     *
     * @Route("/{id}/delete", name="fee_registration_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success', $this->get('translator')->trans('flash.fee.registration.deleted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

        } else {
            $this->get('session')
                    ->setFlash('error', $this->get('translator')->trans('flash.fee.registration.fail.delete'));
        }

        return $this->redirect($this->generateUrl('fee_registration'));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.fee']['links.fee.registration']->setCurrent(true);
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
