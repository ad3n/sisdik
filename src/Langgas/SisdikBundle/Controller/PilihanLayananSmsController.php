<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $em = $this->getDoctrine()->getManager();
        $searchform = $this->createForm('sisdik_carilayanansms');

        $querybuilder = $em->createQueryBuilder()
            ->select('sekolah')
            ->from('LanggasSisdikBundle:Sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder
                    ->where('sekolah.id = :sekolah')
                    ->setParameter("sekolah", $searchdata['sekolah'])
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
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
        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findBy([
                'sekolah' => $id,
                'status' => true,
            ])
        ;

        $sekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($id);

        return [
            'sekolah' => $sekolah,
            'entity' => $entity,
            'daftarJenisLayanan' => $daftarLayanan,
        ];
    }

    /**
     * @Route("/edit/{id}", name="layanansms_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('pilihanLayananSms')
            ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
            ->where('pilihanLayananSms.sekolah = :sekolah')
            ->andWhere('pilihanLayananSms.status = :status')
            ->setParameter('sekolah', $id)
            ->setParameter('status', true)
        ;

        $pilihanLayananSms = $querybuilder->getQuery()->getResult();
        $layananSmsAktif = [];

        foreach ($pilihanLayananSms as $pilihanLayanan) {
            if ($pilihanLayanan instanceof PilihanLayananSms) {
                if ($pilihanLayanan->getStatus() === true) {
                    $layananSmsAktif[$pilihanLayanan->getJenisLayanan()] = $pilihanLayanan->getJenisLayanan();
                }
            }
        }

        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $form = $this->createForm('sisdik_pilihanlayanansms', null, [
            'daftarLayanan' => $daftarLayanan,
            'layananSmsAktif' => $layananSmsAktif,
        ]);

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/update/{id}", name="layanansms_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanLayananSms:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();
        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('pilihanLayananSms')
            ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
            ->where('pilihanLayananSms.sekolah = :sekolah')
            ->andWhere('pilihanLayananSms.status = :status')
            ->setParameter('sekolah', $id)
            ->setParameter('status', true)
        ;

        $pilihanLayananSms = $querybuilder->getQuery()->getResult();
        $layananSmsAktif = [];

        foreach ($pilihanLayananSms as $pilihanLayanan) {
            if ($pilihanLayanan instanceof PilihanLayananSms) {
                if ($pilihanLayanan->getStatus() === true) {
                    $layananSmsAktif[$pilihanLayanan->getJenisLayanan()] = $pilihanLayanan->getJenisLayanan();
                }
            }
        }

        $form = $this->createForm('sisdik_pilihanlayanansms', null, [
            'daftarLayanan' => $daftarLayanan,
            'layananSmsAktif' => $layananSmsAktif,
        ]);

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data = $form->getData();

            foreach ($daftarLayanan as $key => $value) {
                if (array_key_exists('jenislayanan_' . $key, $data) === true) {
                    $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                        ->findOneBy([
                            'sekolah' => $id,
                            'jenisLayanan' => $key,
                        ])
                    ;

                    if ($layananSms instanceof PilihanLayananSms) {
                        if ($data['jenislayanan_' . $key] == 1) {
                            $layananSms->setStatus(true);
                        } else {
                            $layananSms->setStatus(false);
                        }
                        $em->persist($layananSms);
                    } else {
                        if ($data['jenislayanan_' . $key] == 1) {
                            $sekolah= $em->getRepository('LanggasSisdikBundle:Sekolah')->find($id);

                            $layananSms = new PilihanLayananSms();
                            $layananSms->setJenisLayanan($key);
                            $layananSms->setSekolah($sekolah);
                            $layananSms->setStatus(true);
                            $em->persist($layananSms);
                        }
                    }
                }
            }
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.layanan.sms.tersimpan'))
            ;

            return $this->redirect($this->generateUrl('layanansms_show', [
                'id' => $id,
            ]));
        }

        return [
            'daftarLayanan' => $daftarLayanan,
            'form' => $form->createView(),
        ];
    }

    /**
     * Mencari layanan sms yang aktif di sekolah tertentu
     *
     * @Route("layanan-aktif/{id}/", name="layanansms_aktif")
     */
    public function getInfoLayananSms($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->createQueryBuilder()
            ->select('pilihanLayananSms')
            ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
            ->where('pilihanLayananSms.sekolah = :sekolah')
            ->andWhere('pilihanLayananSms.status = :status')
            ->setParameter('sekolah', $id)
            ->setParameter('status', true)
            ->getQuery()
            ->getResult()
        ;

        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $layananAktif = [];
        foreach ($entities as $entity) {
            if ($entity instanceof PilihanLayananSms) {
                $layananAktif[] = $daftarLayanan[$entity->getJenisLayanan()];
            }
        }

        return new JsonResponse($layananAktif);
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.layanansms', [], 'navigations')]->setCurrent(true);
    }
}
