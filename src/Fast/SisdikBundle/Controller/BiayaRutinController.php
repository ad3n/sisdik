<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Fast\SisdikBundle\Form\BiayaSearchFormType;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\BrowserKit\Request;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\BiayaRutin;
use Fast\SisdikBundle\Form\BiayaRutinType;
use Fast\SisdikBundle\Entity\Jenisbiaya;
use Fast\SisdikBundle\Entity\Tahun;
use Fast\SisdikBundle\Entity\Gelombang;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * BiayaRutin controller.
 *
 * @Route("/fee/recur")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class BiayaRutinController extends Controller
{
    /**
     * Lists all BiayaRutin entities.
     *
     * @Route("/", name="fee_recur")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:BiayaRutin', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.jenisbiaya', 't4')
                ->where('t2.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC')->addOrderBy('t3.urutan', 'ASC')
                ->addOrderBy('t.urutan', 'ASC');
        $querybuilder->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t2.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }
            if ($searchdata['gelombang'] != '') {
                $querybuilder->andWhere('t3.id = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder->andWhere("(t4.nama LIKE :jenisbiaya OR t4.kode = :kodejenisbiaya)");
                $querybuilder->setParameter('jenisbiaya', "%{$searchdata['jenisbiaya']}%");
                $querybuilder->setParameter('kodejenisbiaya', $searchdata['jenisbiaya']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a BiayaRutin entity.
     *
     * @Route("/{id}/show", name="fee_recur_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new BiayaRutin entity.
     *
     * @Route("/new", name="fee_recur_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaRutin();
        $form = $this->createForm(new BiayaRutinType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Creates a new BiayaRutin entity.
     *
     * @Route("/create", name="fee_recur_create")
     * @Method("post")
     * @Template("FastSisdikBundle:BiayaRutin:new.html.twig")
     */
    public function createAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaRutin();
        $request = $this->getRequest();
        $form = $this->createForm(new BiayaRutinType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.recur.inserted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.recur');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_recur_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));

        }

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing BiayaRutin entity.
     *
     * @Route("/{id}/edit", name="fee_recur_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaRutinType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing BiayaRutin entity.
     *
     * @Route("/{id}/update", name="fee_recur_update")
     * @Method("post")
     * @Template("FastSisdikBundle:BiayaRutin:edit.html.twig")
     */
    public function updateAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaRutinType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.recur.updated'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.recur');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_recur_edit',
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
     * Deletes a BiayaRutin entity.
     *
     * @Route("/{id}/delete", name="fee_recur_delete")
     * @Method("post")
     */
    public function deleteAction($id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.recur.deleted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

        } else {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.fee.recur.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('fee_recur',
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
        $menu['headings.fee']['links.fee.recur']->setCurrent(true);
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
