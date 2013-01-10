<?php

namespace Fast\SisdikBundle\Controller;
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
use Fast\SisdikBundle\Entity\Tahunmasuk;
use Fast\SisdikBundle\Entity\Gelombang;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BiayaRutin controller.
 *
 * @Route("/fee/recur")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
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
        $idsekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaRutin', 't')->leftJoin('t.idtahunmasuk', 't2')
                ->leftJoin('t.idgelombang', 't3')->leftJoin('t.idjenisbiaya', 't4')
                ->where('t2.idsekolah = :idsekolah')->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.urutan', 'ASC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['idtahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :idtahunmasuk');
                $querybuilder->setParameter('idtahunmasuk', $searchdata['idtahunmasuk']);
            }
            if ($searchdata['idgelombang'] != '') {
                $querybuilder->andWhere('t3.id = :idgelombang');
                $querybuilder->setParameter('idgelombang', $searchdata['idgelombang']);
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder->andWhere("(t4.nama LIKE :jenisbiaya OR t4.kode = :kodejenisbiaya)");
                $querybuilder->setParameter('jenisbiaya', '%' . $searchdata['jenisbiaya'] . '%');
                $querybuilder->setParameter('kodejenisbiaya', $searchdata['jenisbiaya']);
            }
        }
        $querybuilder->setParameter('idsekolah', $idsekolah);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->get('request')->query->get('page', 1));

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
        $idsekolah = $this->isRegisteredToSchool();
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
        $idsekolah = $this->isRegisteredToSchool();
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
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaRutin();
        $request = $this->getRequest();
        $form = $this->createForm(new BiayaRutinType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.fee.recur.inserted'));

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
        $idsekolah = $this->isRegisteredToSchool();
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
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaRutinType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.fee.recur.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_recur_edit',
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
     * Deletes a BiayaRutin entity.
     *
     * @Route("/{id}/delete", name="fee_recur_delete")
     * @Method("post")
     */
    public function deleteAction($id) {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:BiayaRutin')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
            }

            // TODO use count on entity pembayaran to catch if this entity is already used there
            $em->remove($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.fee.recur.deleted'));
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.fee.recur.fail.delete'));
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
