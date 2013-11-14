<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Fast\SisdikBundle\Form\SekolahAsalType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * SekolahAsal controller.
 *
 * @Route("/sekolahasal")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB', 'ROLE_USER')")
 */
class SekolahAsalController extends Controller
{

    /**
     * Lists all SekolahAsal entities.
     *
     * @Route("/", name="sekolahasal")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SimpleSearchFormType());

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:SekolahAsal', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('t.nama LIKE ?1 OR t.kode LIKE ?2 OR t.penghubung LIKE ?3');
                $querybuilder->setParameter(1, "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter(2, "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter(3, "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }
    /**
     * Creates a new SekolahAsal entity.
     *
     * @Route("/", name="sekolahasal_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:SekolahAsal:new.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function createAction(Request $request) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new SekolahAsal();
        $form = $this->createForm(new SekolahAsalType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.sekolah.asal.tersimpan',
                                            array(
                                                    '%nama%' => $entity->getNama(),
                                                    '%kode%' => $entity->getKode()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('sekolahasal_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new SekolahAsal entity.
     *
     * @Route("/new", name="sekolahasal_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function newAction() {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new SekolahAsal();
        $form = $this->createForm(new SekolahAsalType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a SekolahAsal entity.
     *
     * @Route("/{id}", name="sekolahasal_show")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function showAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing SekolahAsal entity.
     *
     * @Route("/{id}/edit", name="sekolahasal_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function editAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
        }

        $editForm = $this->createForm(new SekolahAsalType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing SekolahAsal entity.
     *
     * @Route("/{id}", name="sekolahasal_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:SekolahAsal:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function updateAction(Request $request, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new SekolahAsalType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.sekolah.asal.terbarui',
                                            array(
                                                    '%nama%' => $entity->getNama(),
                                                    '%kode%' => $entity->getKode()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('sekolahasal_edit',
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
     * Deletes a SekolahAsal entity.
     *
     * @Route("/{id}/delete", name="sekolahasal_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:SekolahAsal')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.sekolah.asal.terhapus',
                                                array(
                                                        '%nama%' => $entity->getNama(),
                                                        '%kode%' => $entity->getKode()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.sekolah.asal.gagal.dihapus',
                                            array(
                                                    '%nama%' => $entity->getNama(),
                                                    '%kode%' => $entity->getKode()
                                            )));
        }

        return $this->redirect($this->generateUrl('sekolahasal'));
    }

    /**
     * Mengambil sekolah asal menggunakan kotak autocomplete
     *
     * @param Request $request
     * @Route("/ajax/ambilsekolah", name="sekolahasal_ajax_ambilnama")
     */
    public function ajaxGetSekolahAsal(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:SekolahAsal', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());

        if ($id != '') {
            $querybuilder->andWhere('t.id = :id');
            $querybuilder->setParameter('id', $id);
        } else {
            $querybuilder->andWhere('t.nama LIKE ?1 OR t.kode LIKE ?2');
            $querybuilder->setParameter(1, "%$filter%");
            $querybuilder->setParameter(2, "%$filter%");
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = array();
        foreach ($results as $result) {
            if ($result instanceof SekolahAsal) {
                $retval[] = array(
                    'id' => $result->getId(), 'label' => /** @Ignore */ $result->getNama(), 'value' => $result->getNama(),
                );
            }
        }

        return new Response(json_encode($retval), 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * Creates a form to delete a SekolahAsal entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.pendaftaran', array(), 'navigations')][$this->get('translator')->trans('links.sekolahasal', array(), 'navigations')]->setCurrent(true);
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
