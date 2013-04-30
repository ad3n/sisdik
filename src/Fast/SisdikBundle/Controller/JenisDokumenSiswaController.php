<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SimpleTahunSearchType;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\JenisDokumenSiswa;
use Fast\SisdikBundle\Form\JenisDokumenSiswaType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * JenisDokumenSiswa controller.
 *
 * @Route("/jenisdokumensiswa")
 */
class JenisDokumenSiswaController extends Controller
{

    /**
     * Lists all JenisDokumenSiswa entities.
     *
     * @Route("/", name="jenisdokumensiswa")
     * @PreAuthorize("hasRole('ROLE_ADMIN')")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SimpleTahunSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JenisDokumenSiswa', 't')->leftJoin('t.tahun', 't2')
                ->where('t.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC')->AddOrderBy('t.urutan', 'ASC')
                ->AddOrderBy('t.namaDokumen', 'ASC')->setParameter('sekolah', $sekolah->getId());

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t2.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Displays a form to create a new JenisDokumenSiswa entity.
     *
     * @Route("/new", name="jenisdokumensiswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JenisDokumenSiswa();
        $form = $this->createForm(new JenisDokumenSiswaType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new JenisDokumenSiswa entity.
     *
     * @Route("/", name="jenisdokumensiswa_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisDokumenSiswa:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JenisDokumenSiswa();
        $form = $this->createForm(new JenisDokumenSiswaType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.jenis.dokumen.siswa.tersimpan',
                                            array(
                                                '%nama%' => $entity->getNamaDokumen()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('jenisdokumensiswa_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a JenisDokumenSiswa entity.
     *
     * @Route("/{id}", name="jenisdokumensiswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing JenisDokumenSiswa entity.
     *
     * @Route("/{id}/edit", name="jenisdokumensiswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
        }

        $editForm = $this->createForm(new JenisDokumenSiswaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing JenisDokumenSiswa entity.
     *
     * @Route("/{id}", name="jenisdokumensiswa_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisDokumenSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JenisDokumenSiswaType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.jenis.dokumen.siswa.terbarui',
                                            array(
                                                '%nama%' => $entity->getNamaDokumen()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('jenisdokumensiswa_edit',
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
     * Deletes a JenisDokumenSiswa entity.
     *
     * @Route("/{id}/delete", name="jenisdokumensiswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();
                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.jenis.dokumen.siswa.terhapus',
                                                array(
                                                    '%nama%' => $entity->getNamaDokumen()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.jenis.dokumen.siswa.gagal.dihapus',
                                            array(
                                                '%nama%' => $entity->getNamaDokumen()
                                            )));
        }

        return $this->redirect($this->generateUrl('jenisdokumensiswa'));
    }

    /**
     * Creates a form to delete a JenisDokumenSiswa entity by id.
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
        $menu['headings.setting']['links.jenisdokumensiswa']->setCurrent(true);
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
