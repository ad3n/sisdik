<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/laporan-transaksi-keuangan")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class LaporanTransaksiKeuanganController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const STYLEFILE = "styles.xml";
    const OUTPUTFILE = "laporan-transaksi-keuangan.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-transaksi-keuangan/";

    /**
     * @Route("/", name="laporan-transaksi-keuangan")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $transaksiTercari = 0;
        $tampilkanTercari = false;
        $pencarianJumlahBayar = false;
        $searchkey = '';

        $searchform = $this->createForm('sisdik_caritransaksi');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('transaksi.id'))
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;
        $transaksiTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('transaksi.id'))
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
            ->leftJoin('pembayaran.siswa', 'siswa')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('transaksi')
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
            ->leftJoin('pembayaran.siswa', 'siswa')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->addOrderBy('transaksi.waktuSimpan', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('transaksi.nomorTransaksi LIKE :nomortransaksi OR transaksi.keterangan LIKE :keterangan')
                    ->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $qbsearchnum
                    ->andWhere('transaksi.nomorTransaksi LIKE :nomortransaksi OR transaksi.keterangan LIKE :keterangan')
                    ->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('transaksi.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $qbsearchnum
                    ->andWhere('transaksi.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $tampilkanTercari = true;
            }
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('transaksi.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $qbsearchnum
                    ->andWhere('transaksi.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $tampilkanTercari = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                $querybuilder
                    ->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar")
                    ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                ;

                $qbsearchnum
                    ->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar")
                    ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                ;

                $tampilkanTercari = true;
            }

        }

        $transaksiTercari = intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'transaksiTotal' => $transaksiTotal,
            'transaksiTercari' => $transaksiTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'searchkey' => $searchkey,
            'searchdata' => $searchdata,
            'tanggalSekarang' => new \DateTime(),
        ];
    }

    /**
     * Ekspor data laporan transaksi keuangan
     *
     * @Route("/export", name="laporan-transaksi-keuangan_export")
     * @Method("POST")
     */
    public function exportAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $transaksiTercari = 0;
        $tampilkanTercari = false;
        $pencarianJumlahBayar = false;
        $searchkey = '';
        $judulLaporan = $this->get('translator')->trans('heading.laporan.transaksi.keuangan');

        $searchform = $this->createForm('sisdik_caritransaksi');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('transaksi.id'))
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;
        $transaksiTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('transaksi.id'))
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
            ->leftJoin('pembayaran.siswa', 'siswa')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('transaksi')
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
            ->leftJoin('pembayaran.siswa', 'siswa')
            ->andWhere('transaksi.sekolah = :sekolah')
            ->addOrderBy('transaksi.waktuSimpan', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($searchform->isValid()) {
            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('transaksi.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $qbsearchnum
                    ->andWhere('transaksi.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $tampilkanTercari = true;
                $judulLaporan .= " " . $this->get('translator')->trans('dari.tanggal') . " " . $dariTanggal->format("Y-m-d 00:00:00");
            }

            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('transaksi.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $qbsearchnum
                    ->andWhere('transaksi.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $tampilkanTercari = true;
                $judulLaporan .= " " . $this->get('translator')->trans('hingga.tanggal') . " " . $hinggaTanggal->format("Y-m-d 24:00:00");
            } else {
                $sekarang = new \DateTime();
                $judulLaporan .= " " . $this->get('translator')->trans('hingga.tanggal') . " " . $sekarang->format("Y-m-d H:i:s");
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('transaksi.nomorTransaksi LIKE :nomortransaksi OR transaksi.keterangan LIKE :keterangan')
                    ->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $qbsearchnum
                    ->andWhere('transaksi.nomorTransaksi LIKE :nomortransaksi OR transaksi.keterangan LIKE :keterangan')
                    ->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
                $judulLaporan .= ", " . $this->get('translator')->trans('kata.pencarian') . " " . $searchkey;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                $querybuilder
                    ->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar")
                    ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                ;

                $qbsearchnum
                    ->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar")
                    ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                ;

                $tampilkanTercari = true;
                $judulLaporan .= ", "
                    . $this->get('translator')->trans('jumlah.pembayaran')
                    . " $pembandingBayar "
                    . number_format($searchdata['jumlahBayar'], 0, ',', '.')
                ;
            }
        } else {
            // TODO error response
        }

        $transaksiTercari = intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $stylebase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::STYLEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . date("Y-m-d-h-i") . ".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir . $sekolah->getId() . '/')) {
            $fs->mkdir($outputdir . $sekolah->getId() . '/');
        }

        $documentsource = $outputdir . $sekolah->getId() . '/' . $filesource;
        $documenttarget = $outputdir . $sekolah->getId() . '/' . $filetarget;

        $entities = $querybuilder->getQuery()->getResult();

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:LaporanTransaksiKeuangan:styles.xml.twig"));
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:LaporanTransaksiKeuangan:report.xml.twig", [
                    'entities' => $entities,
                    'transaksiTercari' => $transaksiTercari,
                    'transaksiTotal' => $transaksiTotal,
                    'judulLaporan' => $judulLaporan,
                ]));

                if ($ziparchive->close() === TRUE) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-transaksi-keuangan_download", [
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
    }

    /**
     * Download the generated file report
     *
     * @Route("/download/{filename}/{type}", name="laporan-transaksi-keuangan_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Keuangan Pendaftaran');

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
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.laporan.transaksi.keuangan', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
