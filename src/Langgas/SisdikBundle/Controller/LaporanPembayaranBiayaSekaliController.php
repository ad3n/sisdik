<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/laporan-pembayaran-biaya-sekali-bayar")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class LaporanPembayaranBiayaSekaliController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-pembayaran-biaya-sekali-bayar.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-pembayaran-biaya-sekali-bayar/";

    /**
     * @Route("/", name="laporan-pembayaran-biaya-sekali")
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

        $siswaTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm('sisdik_caripembayaransekali');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
        ;
        $siswaTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.pembayaranSekali', 'pembayaran')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('siswa.nomorIndukSistem', 'DESC')
            ->addOrderBy('siswa.nomorInduk', 'ASC')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorIndukSistem LIKE :nomorIdentitasSisdik '
                        .' OR siswa.nomorInduk LIKE :nomorInduk '
                        .' OR siswa.keterangan LIKE :keterangan ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorIdentitasSisdik', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorInduk', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                // TODO ini seharusnya waktu simpan transaksi atau waktu pembayaran?!
                $querybuilder
                    ->andWhere('pembayaran.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->groupBy('siswa.id')
                ;

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('pembayaran.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                    ->groupBy('siswa.id')
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder
                    ->andWhere('siswa.jenisKelamin = :jeniskelamin')
                    ->setParameter('jeniskelamin', $searchdata['jenisKelamin'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                    $querybuilder
                        ->groupBy('siswa.id')
                    ;

                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        // masukkan pencarian untuk yg belum melakukan transaksi
                        $querybuilder
                            ->having("(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaSekali)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                ." OR SUM(DISTINCT(siswa.sisaBiayaSekali)) < 0")
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaSekali)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar")
                        ;
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);
                } else {
                    $querybuilder
                        ->groupBy('siswa.id')
                        ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar")
                        ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                    ;
                }

                $tampilkanTercari = true;
            }
            if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                $tampilkanTercari = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $siswaTercari = count($querybuilder->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        $summaryform = $this->createForm('sisdik_ringkasanlaporan');

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'siswaTotal' => $siswaTotal,
            'siswaTercari' => $siswaTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'pencarianLanjutan' => $pencarianLanjutan,
            'searchkey' => $searchkey,
            'summaryform' => $summaryform->createView(),
            'searchdata' => $searchdata,
            'tanggalSekarang' => new \DateTime(),
        ];
    }

    /**
     * @Route("/export", name="laporan-pembayaran-biaya-sekali_export")
     * @Method("POST")
     */
    public function exportAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $siswaTercari = 0;
        $judulLaporan = $this->get('translator')->trans('laporan.pembayaran.biaya.sekali.bayar', [], 'headings');

        $searchform = $this->createForm('sisdik_caripembayaransekali');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
        ;
        $siswaTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.pembayaranSekali', 'pembayaran')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('siswa.nomorIndukSistem', 'DESC')
            ->addOrderBy('siswa.nomorInduk', 'ASC')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $judulLaporan .= " ".$this->get('translator')->trans('tahun')." ".$searchdata['tahun']->getTahun();
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorIndukSistem LIKE :nomorIdentitasSisdik '
                        .' OR siswa.nomorInduk LIKE :nomorInduk '
                        .' OR siswa.keterangan LIKE :keterangan ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorIdentitasSisdik', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorInduk', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('kata.pencarian')." ".$searchkey;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                // TODO ini seharusnya waktu simpan transaksi atau waktu pembayaran?!
                $querybuilder
                    ->andWhere('pembayaran.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->groupBy('siswa.id')
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('dari.tanggal')." ".$dariTanggal->format("Y-m-d 00:00:00");
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('pembayaran.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                    ->groupBy('siswa.id')
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('hingga.tanggal')." ".$hinggaTanggal->format("Y-m-d 24:00:00");
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder
                    ->andWhere('siswa.jenisKelamin = :jeniskelamin')
                    ->setParameter('jeniskelamin', $searchdata['jenisKelamin'])
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('jenis.kelamin')." ".($searchdata['jenisKelamin'] == 'L' ? 'Laki-laki' : 'Perempuan');
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                $judulLaporan .= ", "
                    .$this->get('translator')->trans('label.paid.amount.total')
                    ." "
                    .$pembandingBayar
                    ." "
                    .$searchdata['jumlahBayar']
                ;

                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                    $judulLaporan .= "%";
                    $querybuilder->groupBy('siswa.id');

                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        // masukkan pencarian untuk yg belum melakukan transaksi
                        $querybuilder
                            ->having("(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaSekali)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                ." OR SUM(DISTINCT(siswa.sisaBiayaSekali)) < 0")
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaSekali)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar")
                        ;
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);
                } else {
                    $querybuilder
                        ->groupBy('siswa.id')
                        ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar")
                        ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                    ;
                }
            }
        } else {
            // TODO: display error response
        }

        $entities = $querybuilder->getQuery()->getResult();
        $siswaTercari = count($entities);

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE.date("Y-m-d-h-i").".sisdik";

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

        $biayaSekali = $em->getRepository('LanggasSisdikBundle:BiayaSekali')
            ->findBy([
                'tahun' => $searchdata['tahun'],
            ], [
                'urutan' => 'ASC',
            ])
        ;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:LaporanPembayaranBiayaSekali:styles.xml.twig"));
                $ziparchive->addFromString('content.xml', $this->renderView(
                    "LanggasSisdikBundle:LaporanPembayaranBiayaSekali:report.xml.twig",
                    [
                        'entities' => $entities,
                        'siswaTercari' => $siswaTercari,
                        'siswaTotal' => $siswaTotal,
                        'judulLaporan' => $judulLaporan,
                        'biayaSekali' => $biayaSekali,
                        'akhirKolomBiaya' => $this->num2alpha(count($biayaSekali) + 3),
                    ]))
                ;
                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-pembayaran-biaya-sekali_download", [
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
     * @Route("/unduh/{filename}/{type}", name="laporan-pembayaran-biaya-sekali_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR.$sekolah->getId().'/'.$filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Pembayaran Biaya Sekali Bayar');

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

    /**
     * Converts an integer into the alphabet base (A-Z).
     *
     * @param  int    $n This is the number to convert.
     * @return string The converted number.
     * @author Theriault
     *
     */
    private function num2alpha($n)
    {
        $r = '';
        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))).$r;
            $n -= pow(26, $i);
        }

        return $r;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.laporan.pembayaran.biaya.sekali.bayar', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
