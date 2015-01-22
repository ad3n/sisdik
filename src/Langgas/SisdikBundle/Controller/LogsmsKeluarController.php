<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\LogsmsKeluar;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/log-sms")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class LogsmsKeluarController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "logsmskeluar.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/logsmskeluar/";

    /**
     * @Route("/", name="logsmskeluar")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari_logsms');

        return [
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/lihat", name="logsmskeluar_lihat")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:LogsmsKeluar:index.html.twig")
     */
    public function lihatAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari_logsms');

        $qbTotal = $em->createQueryBuilder()
            ->select('COUNT(logsms.id)')
            ->from('LanggasSisdikBundle:LogsmsKeluar', 'logsms')
            ->where('logsms.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $logsmsTotal = $qbTotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('logsms')
            ->from('LanggasSisdikBundle:LogsmsKeluar', 'logsms')
            ->where('logsms.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->orderBy('logsms.waktuPanggilApi', 'DESC')
        ;

        $qbTercari = $em->createQueryBuilder()
            ->select('COUNT(logsms.id)')
            ->from('LanggasSisdikBundle:LogsmsKeluar', 'logsms')
            ->where('logsms.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $tampilkanTercari = false;
        if ($searchform->isValid()) {
            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $qbTercari
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $tampilkanTercari = true;
            } elseif ($dariTanggal instanceof \DateTime && !($hinggaTanggal instanceof \DateTime)) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $qbTercari
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $tampilkanTercari = true;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $hinggaTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $qbTercari
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $hinggaTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("logsms.teks LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $qbTercari
                    ->andWhere("logsms.teks LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }
        }

        $logsmsTercari = $qbTercari->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'dariTanggal' => $searchdata['dariTanggal'],
            'hinggaTanggal' => $searchdata['hinggaTanggal'],
            'searchkey' => $searchdata['searchkey'],
            'tampilkanTercari' => $tampilkanTercari,
            'logsmsTotal' => $logsmsTotal,
            'logsmsTercari' => $logsmsTercari,
        ];
    }

    /**
     * @Route("/ekspor", name="logsmskeluar_ekspor")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:LogsmsKeluar:index.xml.twig")
     */
    public function eksporAction()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari_logsms');

        $qbTotal = $em->createQueryBuilder()
            ->select('COUNT(logsms.id)')
            ->from('LanggasSisdikBundle:LogsmsKeluar', 'logsms')
            ->where('logsms.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $logsmsTotal = $qbTotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('logsms')
            ->from('LanggasSisdikBundle:LogsmsKeluar', 'logsms')
            ->where('logsms.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->orderBy('logsms.waktuPanggilApi', 'DESC')
        ;

        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            $dariTanggal = $searchdata['dariTanggal'] === null ? new \DateTime() : $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            } elseif ($dariTanggal instanceof \DateTime && !($hinggaTanggal instanceof \DateTime)) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('logsms.waktuPanggilApi >= :dariTanggal AND logsms.waktuPanggilApi <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $hinggaTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("logsms.teks LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;
            }
        } else {
            $return = [
                "error" => $this->get('translator')->trans('parameter.pencarian.log.sms.harus.valid'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $logsms = $querybuilder->getQuery()->getResult();

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE.date("Y-m-d h:i").".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput.$extensionsource;
        $filetarget = $filenameoutput.$extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir.$sekolah->getId().'/')) {
            $fs->mkdir($outputdir.$sekolah->getId().'/');
        }

        $documentsource = $outputdir.$sekolah->getId().'/'.$filesource;
        $documenttarget = $outputdir.$sekolah->getId().'/'.$filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:LogsmsKeluar:index.xml.twig", [
                        'searchkey' => $searchdata['searchkey'],
                        'dariTanggal' => $searchdata['dariTanggal'],
                        'hinggaTanggal' => $searchdata['hinggaTanggal'],
                        'logsms' => $logsms,
                        'logsmsTotal' => $logsmsTotal,
                    ])
                );

                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("logsmskeluar_unduh", [
                            'filename' => $filetarget,
                        ]),
                        "filename" => $filetarget,
                    ];

                    $return = json_encode($return);

                    return new Response($return, 200, [
                        'Content-Type' => 'application/json',
                    ]);
                }
            }
        }

        $return = [
            "error" => $this->get('translator')->trans('errorinfo.tak.ada.log.sms.keluar'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/unduh/{filename}/{type}", name="logsmskeluar_unduh")
     * @Method("GET")
     */
    public function unduhAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR.$sekolah->getId().'/'.$filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Log sms keluar');

        if ($type == 'ods') {
            $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        } elseif ($type == 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf');
        }

        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', filesize($documenttarget));

        return $response;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.sms', [], 'navigations')][$translator->trans('links.log.sms.keluar', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
