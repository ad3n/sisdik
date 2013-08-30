<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Tingkat;
use Fast\SisdikBundle\Form\TingkatType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Tingkat controller.
 *
 * @Route("/tingkat-kelas")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class TingkatController extends Controller
{
    /**
     * Lists all Tingkat entities.
     *
     * @Route("/", name="tingkat-kelas")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tingkat', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.kode', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Finds and displays a Tingkat entity.
     *
     * @Route("/{id}/show", name="tingkat-kelas_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Tingkat entity.
     *
     * @Route("/new", name="tingkat-kelas_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new Tingkat();

        $qbe = $em->createQueryBuilder();
        $queryUrutan = $em->createQueryBuilder()->select($qbe->expr()->max('tingkat.urutan'))
                ->from('FastSisdikBundle:Tingkat', 'tingkat')->where('tingkat.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId());
        $nomorUrut = $queryUrutan->getQuery()->getSingleScalarResult();
        $nomorUrut = $nomorUrut === null ? 0 : $nomorUrut;
        $nomorUrut++;

        $entity->setUrutan($nomorUrut);

        $form = $this->createForm(new TingkatType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Tingkat entity.
     *
     * @Route("/create", name="tingkat-kelas_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:Tingkat:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Tingkat();
        $form = $this->createForm(new TingkatType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.tingkat.kelas.tersimpan',
                                            array(
                                                '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('tingkat-kelas_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Tingkat entity.
     *
     * @Route("/{id}/edit", name="tingkat-kelas_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
        }

        $editForm = $this->createForm(new TingkatType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Tingkat entity.
     *
     * @Route("/{id}/update", name="tingkat-kelas_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Tingkat:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new TingkatType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.tingkat.kelas.terbarui',
                                            array(
                                                '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('tingkat-kelas_edit',
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
     * Deletes a Tingkat entity.
     *
     * @Route("/{id}/delete", name="tingkat-kelas_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Tingkat')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.tingkat.kelas.terhapus',
                                                array(
                                                    '%nama%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.tingkat.kelas.gagal.dihapus'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('tingkat-kelas',
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
        $menu['headings.academic']['links.tingkat']->setCurrent(true);
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
