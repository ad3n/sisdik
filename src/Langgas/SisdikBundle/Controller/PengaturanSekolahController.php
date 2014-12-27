<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pengaturan-sekolah")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class PengaturanSekolahController extends Controller
{
    /**
     * @Route("/", name="settings_school_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('sekolah')
            ->from('LanggasSisdikBundle:Sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform = $this->createForm('sisdik_cari');
        $searchform->submit($request);
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->where('sekolah.nama LIKE ?1')
                    ->orWhere('sekolah.kode LIKE ?2')
                    ->setParameter(1, "%{$searchdata['searchkey']}%")
                    ->setParameter(2, "%{$searchdata['searchkey']}%")
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'form' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/new", name="settings_school_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $sekolah = new Sekolah();
        $form = $this->createForm('sisdik_sekolah', $sekolah);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $qbe = $em->createQueryBuilder();

                $querynomor = $em->createQueryBuilder()
                    ->select($qbe->expr()->max('sekolah.nomorUrut'))
                    ->from('LanggasSisdikBundle:Sekolah', 'sekolah')
                ;

                $nomorUrut = $querynomor->getQuery()->getSingleScalarResult();
                $nomorUrut = $nomorUrut === null ? 0 : $nomorUrut;
                $nomorUrut++;
                $sekolah->setNomorUrut($nomorUrut);

                $em->persist($sekolah);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.school.inserted', [
                        '%schoolname%' => $sekolah->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('settings_school_list'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/edit/{id}", name="settings_school_edit")
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $sekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($id);

        $form = $this->createForm('sisdik_sekolah', $sekolah);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                if ($form['fileUpload']->getData() !== null) {
                    $sekolah->setLogo("temp_".uniqid(mt_rand(), true));
                }
                $em->persist($sekolah);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.school.updated', [
                        '%schoolname%' => $sekolah->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('settings_school_list'));
            }
        }

        return [
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * @Route("/delete/{id}/{confirmed}", name="settings_school_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     */
    public function deleteAction($id, $confirmed)
    {
        $em = $this->getDoctrine()->getManager();

        $sekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($id);

        if ($confirmed == 1) {
            try {
                $em->remove($sekolah);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.school.deleted', [
                        '%schoolname%' => $sekolah->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('settings_school_list'));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($exception);
            }
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.settings.school.fail.delete', [
                '%schoolname%' => $sekolah->getNama(),
            ]))
        ;

        return $this->redirect($this->generateUrl('settings_school_list'));
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.schools', [], 'navigations')]->setCurrent(true);
    }
}
