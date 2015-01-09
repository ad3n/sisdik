<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pilihan-layanan-sms")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class PilihanLayananSmsController extends Controller
{
    /**
     * @Route("/", name="layanansms")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carilayanansms');

        $querybuilder = $em->createQueryBuilder()
            ->select('pilihanLayananSms')
            ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
            ->leftJoin('pilihanLayananSms.sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
            ->addOrderBy('pilihanLayananSms.jenisLayanan', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder
                    ->where('pilihanLayananSms.sekolah = :sekolah')
                    ->setParameter("sekolah", $searchdata['sekolah'])
                ;
            }

            if ($searchdata['jenisLayanan'] != '') {
                $querybuilder
                    ->andWhere('pilihanLayananSms.jenisLayanan = :jenis')
                    ->setParameter("jenis", $searchdata['jenisLayanan'])
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'daftarJenisLayanan' => array_merge(
                PilihanLayananSms::getDaftarLayananPendaftaran(),
                PilihanLayananSms::getDaftarLayananLaporan(),
                PilihanLayananSms::getDaftarLayananKehadiran(),
                PilihanLayananSms::getDaftarLayananKepulangan(),
                PilihanLayananSms::getDaftarLayananBiayaSekaliBayar(),
                PilihanLayananSms::getDaftarLayananLain(),
                PilihanLayananSms::getDaftarLayananPeriodik()
            ),
        ];
    }

    /**
     * @Route("/new", name="layanansms_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new PilihanLayananSms();
        $form = $this->createForm('sisdik_pilihanlayanansms', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="layanansms_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanLayananSms:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new PilihanLayananSms();
        $form = $this->createForm('sisdik_pilihanlayanansms', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.layanansms.tersimpan', [
                        '%sekolah%' => $entity->getSekolah()->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanansms');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('layanansms_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="layanansms_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarJenisLayanan' => array_merge(
                PilihanLayananSms::getDaftarLayananPendaftaran(),
                PilihanLayananSms::getDaftarLayananLaporan(),
                PilihanLayananSms::getDaftarLayananKehadiran(),
                PilihanLayananSms::getDaftarLayananKepulangan(),
                PilihanLayananSms::getDaftarLayananBiayaSekaliBayar(),
                PilihanLayananSms::getDaftarLayananLain(),
                PilihanLayananSms::getDaftarLayananPeriodik()
            ),
        ];
    }

    /**
     * @Route("/{id}/edit", name="layanansms_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_pilihanlayanansms', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="layanansms_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanLayananSms:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_pilihanlayanansms', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.layanansms.terbarui', [
                        '%sekolah%' => $entity->getSekolah()->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanansms');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('layanansms_edit', [
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
     * @Route("/{id}/delete", name="layanansms_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.layanansms.terhapus', [
                    '%sekolah%' => $entity->getSekolah()->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.layanansms.gagal.dihapus', [
                    '%sekolah%' => $entity->getSekolah()->getNama(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('layanansms'));
    }

    /**
     * @param mixed $id
     *
     * @return Symfony\Component\Form\Form
     */
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
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.layanansms', [], 'navigations')]->setCurrent(true);
    }
}
