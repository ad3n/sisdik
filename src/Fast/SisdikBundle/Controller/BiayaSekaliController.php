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
use Fast\SisdikBundle\Entity\BiayaSekali;
use Fast\SisdikBundle\Form\BiayaSekaliType;
use Fast\SisdikBundle\Entity\Jenisbiaya;
use Fast\SisdikBundle\Entity\Tahunmasuk;
use Fast\SisdikBundle\Entity\Gelombang;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * BiayaSekali controller.
 *
 * @Route("/fee/once")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_BENDAHARA')")
 */
class BiayaSekaliController extends Controller
{
    /**
     * Lists all BiayaSekali entities.
     *
     * @Route("/", name="fee_once", defaults={"filter"="1"})
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaSekali', 't')->leftJoin('t.tahunmasuk', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.jenisbiaya', 't4')
                ->where('t2.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.urutan', 'ASC');
        $querybuilder->setParameter('sekolah', $sekolah->getId());

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :tahunmasuk');
                $querybuilder->setParameter('tahunmasuk', $searchdata['tahunmasuk']->getId());
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
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a BiayaSekali entity.
     *
     * @Route("/{id}/show", name="fee_once_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new BiayaSekali entity.
     *
     * @Route("/new", name="fee_once_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaSekali();
        $form = $this->createForm(new BiayaSekaliType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Creates a new BiayaSekali entity.
     *
     * @Route("/create", name="fee_once_create")
     * @Method("post")
     * @Template("FastSisdikBundle:BiayaSekali:new.html.twig")
     */
    public function createAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaSekali();
        $request = $this->getRequest();
        $form = $this->createForm(new BiayaSekaliType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')->trans('flash.fee.once.inserted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_once_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));

        }

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing BiayaSekali entity.
     *
     * @Route("/{id}/edit", name="fee_once_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaSekaliType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing BiayaSekali entity.
     *
     * @Route("/{id}/update", name="fee_once_update")
     * @Method("post")
     * @Template("FastSisdikBundle:BiayaSekali:edit.html.twig")
     */
    public function updateAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaSekaliType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')->trans('flash.fee.once.updated'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_once_edit',
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
     * Deletes a BiayaSekali entity.
     *
     * @Route("/{id}/delete", name="fee_once_delete")
     * @Method("post")
     */
    public function deleteAction($id) {
        $sekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:BiayaSekali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')->trans('flash.fee.once.deleted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.fee.once.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('fee_once',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    private function createDeleteForm($id) {
        return $this
                ->createFormBuilder(
                        array(
                            'id' => $id
                        ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.fee']['links.fee.once']->setCurrent(true);
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
