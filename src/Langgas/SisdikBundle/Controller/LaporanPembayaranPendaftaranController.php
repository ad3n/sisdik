<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Entity\SekolahAsal;
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
 * @Route("/laporan-pembayaran-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class LaporanPembayaranPendaftaranController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-pembayaran-pendaftaran.";
    const OUTPUTSUMMARYFILE = "ringkasan-pembayaran-pendaftaran.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-pembayaran-psb/";

    /**
     * @Route("/", name="laporan-pembayaran-pendaftaran")
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

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm('sisdik_caripembayaranpendaftaran');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $pendaftarTotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.sekolahAsal', 'sekolahasal')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('ortuaktif', true)
            ->setParameter('melaluiProsesPendaftaran', true)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap'
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan'
                        .' OR siswa.alamat LIKE :alamat '
                        .' OR orangtua.nama LIKE :namaortu '
                        .' OR orangtua.ponsel LIKE :ponselortu ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomor', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('alamat', "%{$searchdata['searchkey']}%")
                    ->setParameter('namaortu', "%{$searchdata['searchkey']}%")
                    ->setParameter('ponselortu', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
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

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder
                    ->andWhere('siswa.sekolahAsal = :sekolahasal')
                    ->setParameter('sekolahasal', $searchdata['sekolahAsal'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder
                    ->andWhere('siswa.referensi = :referensi')
                    ->setParameter('referensi', $searchdata['referensi'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if (isset($searchdata['jumlahBayar']) && $searchdata['jumlahBayar'] >= 0) {
                $querybuilder
                    ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                    ->groupBy('siswa.id')
                ;

                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        $querybuilder
                            ->having("(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                ." OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                               ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar")
                        ;
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);
                } else {
                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || (($pembandingBayar == '=' || $pembandingBayar == '>=') && $searchdata['jumlahBayar'] == 0)) {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                            ->orHaving("SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                            ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar")
                            ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                        ;
                    }
                }

                $tampilkanTercari = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $qbTercari = clone $querybuilder;
        $pendaftarTercari = count($qbTercari->select('DISTINCT(siswa.id)')->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        $summaryform = $this->createForm('sisdik_ringkasanlaporan');

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'pendaftarTotal' => $pendaftarTotal,
            'pendaftarTercari' => $pendaftarTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'pencarianLanjutan' => $pencarianLanjutan,
            'searchkey' => $searchkey,
            'summaryform' => $summaryform->createView(),
            'searchdata' => $searchdata,
            'tanggalSekarang' => new \DateTime(),
        ];
    }

    /**
     * @Route("/export", name="laporan-pembayaran-pendaftaran_export")
     * @Method("POST")
     */
    public function exportAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $judulLaporan = $this->get('translator')->trans('heading.laporan.pembayaran.pendaftaran');

        $searchform = $this->createForm('sisdik_caripembayaranpendaftaran');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $pendaftarTotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.sekolahAsal', 'sekolahasal')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('ortuaktif', true)
            ->setParameter('melaluiProsesPendaftaran', true)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $judulLaporan .= " ".$this->get('translator')->trans('tahun')." ".$searchdata['tahun']->getTahun();
            }

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;

                $judulLaporan .= " ".$searchdata['gelombang']->getNama();
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap'
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan'
                        .' OR siswa.alamat LIKE :alamat '
                        .' OR orangtua.nama LIKE :namaortu '
                        .' OR orangtua.ponsel LIKE :ponselortu ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomor', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('alamat', "%{$searchdata['searchkey']}%")
                    ->setParameter('namaortu', "%{$searchdata['searchkey']}%")
                    ->setParameter('ponselortu', "%{$searchdata['searchkey']}%")
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('kata.pencarian')." ".$searchkey;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('dari.tanggal')." ".$dariTanggal->format("Y-m-d 00:00:00");
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
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

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder
                    ->andWhere('siswa.sekolahAsal = :sekolahasal')
                    ->setParameter('sekolahasal', $searchdata['sekolahAsal'])
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('sekolah.asal')." ".$searchdata['sekolahAsal']->getNama();
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder
                    ->andWhere('siswa.referensi = :referensi')
                    ->setParameter('referensi', $searchdata['referensi'])
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('referensi')." ".$searchdata['referensi']->getNama();
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if (isset($searchdata['jumlahBayar']) && $searchdata['jumlahBayar'] >= 0) {
                $judulLaporan .= ", "
                    .$this->get('translator')->trans('label.paid.amount.total')
                    ." "
                    .$pembandingBayar
                    ." "
                    .$searchdata['jumlahBayar']
                ;

                $querybuilder
                    ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                    ->groupBy('siswa.id')
                ;

                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        $querybuilder
                            ->having("(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                ." OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                               ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar")
                        ;
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);
                } else {
                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || (($pembandingBayar == '=' || $pembandingBayar == '>=') && $searchdata['jumlahBayar'] == 0)) {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                            ->orHaving("SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                            ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar")
                            ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                        ;
                    }
                }
            }
        } else {
            // TODO: display error response
        }

        $qbTercari = clone $querybuilder;
        $pendaftarTercari = count($qbTercari->select('DISTINCT(siswa.id)')->getQuery()->getResult());

        $entities = $querybuilder->getQuery()->getResult();

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

        $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
            ->findBy([
                'tahun' => $searchdata['tahun'],
                'gelombang' => $searchdata['gelombang'],
            ], [
                'urutan' => 'ASC',
            ])
        ;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:LaporanPembayaranPendaftaran:styles.xml.twig"));
                $ziparchive->addFromString('content.xml', $this->renderView(
                    "LanggasSisdikBundle:LaporanPembayaranPendaftaran:report.xml.twig",
                    [
                        'entities' => $entities,
                        'pendaftarTercari' => $pendaftarTercari,
                        'pendaftarTotal' => $pendaftarTotal,
                        'judulLaporan' => $judulLaporan,
                        'biayaPendaftaran' => $biayaPendaftaran,
                        'akhirKolomBiaya' => $this->num2alpha(count($biayaPendaftaran) + 3),
                    ]))
                ;
                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-pembayaran-pendaftaran_download", [
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
     * @Route("/unduh/{filename}/{type}", name="laporan-pembayaran-pendaftaran_download")
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
        $response->headers->set('Content-Description', 'Laporan Pembayaran Pendaftaran');

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
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.laporan.pembayaran.pendaftaran', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
