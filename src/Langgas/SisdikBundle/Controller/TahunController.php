<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Tahun;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/year")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class TahunController extends Controller
{
    /**
     * @Route("/", name="settings_year")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{id}/show", name="settings_year_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="settings_year_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Tahun();
        $form = $this->createForm('sisdik_tahun', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="settings_year_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Tahun:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new Tahun();
        $form = $this->createForm('sisdik_tahun', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.year.inserted', [
                        '%year%' => $entity->getTahun(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.year');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('settings_year_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="settings_year_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        // restrict modification if the current Tahun is already used elsewhere
        if ($this->isEntityUsedElsewhere($id)) {
            $message = $this->get('translator')->trans('exception.update.year.restrict');
            throw new DBALException($message);
        }

        $entity = $em->getRepository('LanggasSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_tahun', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="settings_year_update")
     * @Method("post")
     * @Template("LanggasSisdikBundle:Tahun:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        // restrict modification if the current Tahun is already used elsewhere
        if ($this->isEntityUsedElsewhere($id)) {
            $message = $this->get('translator')->trans('exception.update.year.restrict');
            throw new DBALException($message);
        }

        $entity = $em->getRepository('LanggasSisdikBundle:Tahun')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_tahun', $entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.year.updated', [
                        '%year%' => $entity->getTahun(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.year');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('settings_year_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="settings_year_delete")
     * @Method("post")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->getSekolah();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Tahun')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Tahun tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.year.deleted', [
                        '%year%' => $entity->getTahun(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.settings.year.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('settings_year'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id,
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.year', [], 'navigations')]->setCurrent(true);
    }

    /**
     * Check if current entity is already used elsewhere
     *
     * @param $id
     */
    private function isEntityUsedElsewhere($id)
    {
        $em = $this->getDoctrine()->getManager();

        $biayaRutin = $em->getRepository('LanggasSisdikBundle:BiayaRutin')
            ->findOneBy([
                'tahun' => $id,
            ])
        ;

        $biayaSekali = $em->getRepository('LanggasSisdikBundle:BiayaSekali')
            ->findOneBy([
                'tahun' => $id,
            ])
        ;

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
            ->findOneBy([
                'tahun' => $id,
            ])
        ;

        $panitiaPendaftaran = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')
            ->findOneBy([
                'tahun' => $id,
            ])
        ;

        $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
            ->findOneBy([
                'tahun' => $id,
            ])
        ;

        if ($biayaSekali || $biayaRutin || $siswa || $panitiaPendaftaran || $biayaPendaftaran) {
            return true;
        }

        return false;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
