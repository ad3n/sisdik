<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Tahunmasuk;
use Fast\SisdikBundle\Form\TahunmasukType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Tahunmasuk controller.
 *
 * @Route("/yearentry")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class TahunmasukController extends Controller
{
    /**
     * Lists all Tahunmasuk entities.
     *
     * @Route("/", name="settings_yearentry")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahunmasuk', 't')
                ->leftJoin('t.panitiaPendaftaran', 't2')->where('t.sekolah = :sekolah')
                ->orderBy('t.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * Finds and displays a Tahunmasuk entity.
     *
     * @Route("/{id}/show", name="settings_yearentry_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Tahunmasuk')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahunmasuk tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Tahunmasuk entity.
     *
     * @Route("/new", name="settings_yearentry_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Tahunmasuk();
        $form = $this->createForm(new TahunmasukType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Creates a new Tahunmasuk entity.
     *
     * @Route("/create", name="settings_yearentry_create")
     * @Method("post")
     * @Template("FastSisdikBundle:Tahunmasuk:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Tahunmasuk();
        $form = $this->createForm(new TahunmasukType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.yearentry.inserted',
                                                array(
                                                    '%yearentry%' => $entity->getTahun()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.yearentry');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_yearentry_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));

        }

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing Tahunmasuk entity.
     *
     * @Route("/{id}/edit", name="settings_yearentry_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        // restrict modification if the current Tahunmasuk is already used elsewhere
        if ($this->isEntityUsedElsewhere($id)) {
            $message = $this->get('translator')->trans('exception.update.yearentry.restrict');
            throw new DBALException($message);
        }

        $entity = $em->getRepository('FastSisdikBundle:Tahunmasuk')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahunmasuk tak ditemukan.');
        }

        $editForm = $this->createForm(new TahunmasukType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Tahunmasuk entity.
     *
     * @Route("/{id}/update", name="settings_yearentry_update")
     * @Method("post")
     * @Template("FastSisdikBundle:Tahunmasuk:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        // restrict modification if the current Tahunmasuk is already used elsewhere
        if ($this->isEntityUsedElsewhere($id)) {
            $message = $this->get('translator')->trans('exception.update.yearentry.restrict');
            throw new DBALException($message);
        }

        $entity = $em->getRepository('FastSisdikBundle:Tahunmasuk')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahunmasuk tak ditemukan.');
        }

        $editForm = $this->createForm(new TahunmasukType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->bind($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.yearentry.updated',
                                                array(
                                                    '%yearentry%' => $entity->getTahun()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.yearentry');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_yearentry_edit',
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
     * Deletes a Tahunmasuk entity.
     *
     * @Route("/{id}/delete", name="settings_yearentry_delete")
     * @Method("post")
     */
    public function deleteAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Tahunmasuk')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Tahunmasuk tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.yearentry.deleted',
                                                array(
                                                    '%yearentry%' => $entity->getTahun()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.settings.yearentry.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('settings_yearentry',
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
        $menu['headings.setting']['links.yearentry']->setCurrent(true);
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

    /**
     * Check if current entity is already used elsewhere
     *
     * @param $id
     */
    private function isEntityUsedElsewhere($id) {
        $em = $this->getDoctrine()->getManager();

        $biayaRutinEntity = $em->getRepository('FastSisdikBundle:BiayaRutin')
                ->findOneBy(array(
                    'tahunmasuk' => $id
                ));
        $biayaSekaliEntity = $em->getRepository('FastSisdikBundle:BiayaSekali')
                ->findOneBy(array(
                    'tahunmasuk' => $id
                ));
        $siswaEntity = $em->getRepository('FastSisdikBundle:Siswa')
                ->findOneBy(array(
                    'tahunmasuk' => $id
                ));
        if ($biayaSekaliEntity || $biayaRutinEntity || $siswaEntity) {
            return true;
        }
        return false;
    }
}
