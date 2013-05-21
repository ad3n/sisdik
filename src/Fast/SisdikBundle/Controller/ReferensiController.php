<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Symfony\Component\HttpFoundation\Response;
use Fast\SisdikBundle\Util\RuteAsal;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Referensi;
use Fast\SisdikBundle\Form\ReferensiType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Referensi controller.
 *
 * @Route("/referensi")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB', 'ROLE_USER')")
 */
class ReferensiController extends Controller
{

    /**
     * Lists all Referensi entities.
     *
     * @Route("/", name="referensi")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SimpleSearchFormType());

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Referensi', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('t.nama LIKE :nama');
                $querybuilder->setParameter('nama', "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Displays a form to create a new Referensi entity.
     *
     * @Route("/new", name="referensi_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function newAction() {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Referensi();
        $form = $this->createForm(new ReferensiType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Referensi entity.
     *
     * @Route("/", name="referensi_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:Referensi:new.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function createAction(Request $request) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Referensi();
        $form = $this->createForm(new ReferensiType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.perujuk.tersimpan',
                                            array(
                                                '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('referensi_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Referensi entity.
     *
     * @Route("/{id}", name="referensi_show")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function showAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Referensi entity.
     *
     * @Route("/{id}/edit", name="referensi_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function editAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $editForm = $this->createForm(new ReferensiType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Referensi entity.
     *
     * @Route("/{id}", name="referensi_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Referensi:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function updateAction(Request $request, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ReferensiType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.perujuk.terbarui',
                                            array(
                                                '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('referensi_edit',
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
     * Deletes a Referensi entity.
     *
     * @Route("/{id}/delete", name="referensi_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Referensi')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.perujuk.terhapus',
                                                array(
                                                    '%nama%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.perujuk.gagal.dihapus',
                                            array(
                                                '%nama%' => $entity->getNama()
                                            )));
        }

        return $this->redirect($this->generateUrl('referensi'));
    }

    /**
     * Mengambil referensi menggunakan kotak autocomplete
     *
     * @param Request $request
     * @Route("/ajax/ambilreferensi", name="referensi_ajax_ambilnama")
     */
    public function ajaxGetReferensi(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Referensi', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());

        if ($id != '') {
            $querybuilder->andWhere('t.id = :id');
            $querybuilder->setParameter('id', $id);
        } else {
            $querybuilder->andWhere('t.nama LIKE :filter');
            $querybuilder->setParameter('filter', "%$filter%");
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = array();
        foreach ($results as $result) {
            if ($result instanceof Referensi) {
                $retval[] = array(
                    'id' => $result->getId(), 'label' => $result->getNama(), 'value' => $result->getNama(),
                );
            }
        }

        return new Response(json_encode($retval), 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * Creates a form to delete a Referensi entity by id.
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
        $menu['headings.academic']['links.referensi']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
