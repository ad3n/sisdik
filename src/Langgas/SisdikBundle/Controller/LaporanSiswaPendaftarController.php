<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Util\Messenger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/laporan-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB')")
 */
class LaporanSiswaPendaftarController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-pendaftaran.";
    const OUTPUTSUMMARYFILE = "ringkasan-pendaftaran.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-psb/";

    /**
     * @Route("/", name="laporan-pendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm('sisdik_carilaporanpendaftar');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $pendaftarTotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $pendaftarTertahkik = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('ortuaktif', true)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
        ;

        if ($searchform->isValid()) {
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
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan '
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
                    ->andWhere('siswa.sekolahAsal = :sekolahAsal')
                    ->setParameter('sekolahAsal', $searchdata['sekolahAsal'])
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
            if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                $tampilkanTercari = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $qbTercari = clone $querybuilder;
        $pendaftarTercari = count($qbTercari->select('DISTINCT(siswa.id)')->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $summaryform = $this->createForm('sisdik_ringkasanlaporan');

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'panitiaAktif' => $panitiaAktif,
            'pendaftarTotal' => $pendaftarTotal,
            'pendaftarTertahkik' => $pendaftarTertahkik,
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
     * @Route("/export", name="laporan-pendaftaran_export")
     * @Method("POST")
     */
    public function exportAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $judulLaporan = $this->get('translator')->trans('laporan.pendaftaran.siswa', [], 'headings');

        $tahun = $em->getRepository('LanggasSisdikBundle:Tahun')->find($panitiaAktif[2]);
        $judulLaporan .= " ".$this->get('translator')->trans('label.tahun')." ".$tahun->getTahun();

        $pendaftarTercari = 0;

        $searchform = $this->createForm('sisdik_carilaporanpendaftar');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $pendaftarTotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $pendaftarTertahkik = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('tahun.id = :tahunaktif')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('ortuaktif', true)
            ->setParameter('tahunaktif', $panitiaAktif[2])
            ->setParameter('melaluiProsesPendaftaran', true)
        ;

        if ($searchform->isValid()) {
            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;

                $judulLaporan .= " ".$this->get('translator')->trans('gelombang')." ".$searchdata['gelombang']->getNama();
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan '
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
                    ->andWhere('siswa.sekolahAsal = :sekolahAsal')
                    ->setParameter('sekolahAsal', $searchdata['sekolahAsal'])
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('sekolah.asal')." ".$searchdata['sekolahAsal']->getNama();
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder
                    ->andWhere('siswa.referensi = :referensi')
                    ->setParameter('referensi', $searchdata['referensi'])
                ;

                $judulLaporan .= ", ".$this->get('translator')->trans('dengan.referensi')." ".$searchdata['referensi']->getNama();
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
        $pendaftarTercari = count($qbTercari->select('siswa.id')->getQuery()->getResult());

        $entities = $querybuilder->getQuery()->getResult();

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $patterns = ['/\s+/', '/\//'];
        $replacements = ['', '_'];
        $filenameoutput = self::OUTPUTFILE.preg_replace($patterns, $replacements, $panitiaAktif[3]).'-'.date("m-d-h-i").".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput.$extensionsource;
        $filetarget = $filenameoutput.$extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir.$sekolah->getId().'/'.$panitiaAktif[3])) {
            $fs->mkdir($outputdir.$sekolah->getId().'/'.$panitiaAktif[3]);
        }

        $documentsource = $outputdir.$sekolah->getId().'/'.$panitiaAktif[3].'/'.$filesource;
        $documenttarget = $outputdir.$sekolah->getId().'/'.$panitiaAktif[3].'/'.$filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:LaporanSiswaPendaftar:styles.xml.twig"));
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:LaporanSiswaPendaftar:report.xml.twig", [
                    'entities' => $entities,
                    'pendaftarTercari' => $pendaftarTercari,
                    'pendaftarTertahkik' => $pendaftarTertahkik,
                    'pendaftarTotal' => $pendaftarTotal,
                    'judulLaporan' => $judulLaporan,
                ]));
                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-pendaftaran_download", [
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
     * @Route("/download/{filename}/{type}", name="laporan-pendaftaran_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();
        $panitiaAktif = $this->getPanitiaAktif();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR.$sekolah->getId().'/'.$panitiaAktif[3].'/'.$filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Pendaftaran');

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
     * @Route("/ringkasan", name="laporan-pendaftaran_summary")
     * @Method("POST")
     * @Secure(roles="ROLE_KETUA_PANITIA_PSB")
     */
    public function summaryAction()
    {
        $sekolah = $this->getSekolah();
        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();

        $summaryform = $this->createForm('sisdik_ringkasanlaporan');
        $summaryform->submit($this->getRequest());
        $summarydata = $summaryform->getData();

        if ($summarydata['output'] === 'sms' && $summarydata['nomorPonsel'] === null) {
            $message = $this->get('translator')->trans('errorinfo.nomor.ponsel.tak.boleh.kosong');
            $summaryform->get('nomorPonsel')->addError(new FormError($message));
        }

        if ($summaryform->isValid()) {
            if ($summarydata['output'] == 'pdf') {
                $filename = self::OUTPUTSUMMARYFILE.preg_replace('/\s+/', '', $panitiaAktif[3]).'-'.date("m-d-h-i").".sisdik.pdf";
                $outputdir = self::DOCUMENTS_OUTPUTDIR;

                $fs = new Filesystem();
                if (!$fs->exists($outputdir.$sekolah->getId().'/'.$panitiaAktif[3])) {
                    $fs->mkdir($outputdir.$sekolah->getId().'/'.$panitiaAktif[3]);
                }

                $documenttarget = $outputdir.$sekolah->getId().'/'.$panitiaAktif[3].'/'.$filename;

                $facade = $this->get('ps_pdf.facade');
                $tmpResponse = new Response();

                $this
                    ->render('LanggasSisdikBundle:LaporanSiswaPendaftar:summary.pdf.twig', [
                        'sekolah' => $sekolah,
                        'teks' => $summarydata['teksTerformat'],
                    ], $tmpResponse)
                ;

                $xml = $tmpResponse->getContent();
                $content = $facade->render($xml);

                $fp = fopen($documenttarget, "w");

                if (!$fp) {
                    throw new IOException($translator->trans("exception.open.file.pdf"));
                } else {
                    fwrite($fp, $content);
                    fclose($fp);
                }

                return $this->redirect($this->generateUrl('laporan-pendaftaran_download', [
                    'filename' => $filename,
                    'type' => 'pdf',
                ]));
            } elseif ($summarydata['output'] == 'sms') {
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'e-laporan-ringkasan',
                    ])
                ;

                $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                    ])
                ;

                if ($pilihanLayananSms instanceof PilihanLayananSms) {
                    if ($pilihanLayananSms->getStatus()) {
                        $nomorponsel = preg_split("/[\s,\/]+/", $summarydata['nomorPonsel']);
                        foreach ($nomorponsel as $ponsel) {
                            $messenger = $this->get('sisdik.messenger');
                            if ($messenger instanceof Messenger) {
                                if ($vendorSekolah instanceof VendorSekolah) {
                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                        $messenger->setUseVendor(true);
                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                    }
                                }
                                $messenger->setPhoneNumber($ponsel);
                                $messenger->setMessage($summarydata['teksTerformat']);
                                $messenger->sendMessage($sekolah);
                            }
                        }
                    }
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.ringkasan.laporan.pendaftaran.sms.berhasil.dikirim'))
                ;
            }
        } elseif ($summarydata['output'] == 'sms' && $summarydata['nomorPonsel'] === null) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('errorinfo.nomor.ponsel.tak.boleh.kosong'))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.ringkasan.laporan.pendaftaran.gagal.dibuat'))
            ;
        }

        return $this->redirect($this->generateUrl('laporan-pendaftaran'));
    }

    /**
     * @Route("/format", name="laporan-pendaftaran_format")
     * @Method("GET")
     */
    public function formatTemplateAction()
    {
        $this->getSekolah();

        $teks = $this->getRequest()->query->get('teks');

        $teks = $this->formatTemplate(
            $teks,
            $this->getRequest()->query->get('gelombang'),
            $this->getRequest()->query->get('dariTanggal'),
            $this->getRequest()->query->get('hinggaTanggal'),
            $this->getRequest()->query->get('jenisKelamin'),
            $this->getRequest()->query->get('sekolahAsal'),
            $this->getRequest()->query->get('pembandingBayar'),
            $this->getRequest()->query->get('jumlahBayar'),
            $this->getRequest()->query->get('persenBayar'),
            $this->getRequest()->query->get('referensi'),
            $this->getRequest()->query->get('pendaftarTotal'),
            $this->getRequest()->query->get('pendaftarTercari'),
            $this->getRequest()->query->get('tanggalSekarang')
        );

        $return = [
            'teksterformat' => $teks,
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @param  string $teks
     * @param  string $gelombang
     * @param  string $dariTanggal
     * @param  string $hinggaTanggal
     * @param  string $jenisKelamin
     * @param  string $sekolahAsal
     * @param  string $pembandingBayar
     * @param  string $jumlahBayar
     * @param  string $persenBayar
     * @param  string $referensi
     * @param  string $pendaftarTotal
     * @param  string $pendaftarTercari
     * @param  string $tanggalSekarang
     * @return string $teks
     */
    private function formatTemplate(
        $teks,
        $gelombang = '',
        $dariTanggal = '',
        $hinggaTanggal = '',
        $jenisKelamin = '',
        $sekolahAsal = '',
        $pembandingBayar = '',
        $jumlahBayar = '',
        $persenBayar = '',
        $referensi = '',
        $pendaftarTotal = '',
        $pendaftarTercari = '',
        $tanggalSekarang = ''
    ) {
        $teks = str_replace("%gelombang%", $gelombang, $teks);
        $teks = str_replace("%dari-tanggal%", $dariTanggal, $teks);
        $teks = str_replace("%hingga-tanggal%", $hinggaTanggal, $teks);
        $teks = str_replace("%jenis-kelamin%", $jenisKelamin, $teks);
        $teks = str_replace("%sekolah-asal%", $sekolahAsal, $teks);

        $teks = str_replace("%pembanding-bayar%", $pembandingBayar, $teks);
        $teks = str_replace("%jumlah-bayar%", $jumlahBayar, $teks);
        $teks = str_replace("%persen-bayar%", ($persenBayar == '1') ? '%' : '', $teks);

        $teks = str_replace("%perujuk%", $referensi, $teks);
        $teks = str_replace("%jumlah-total%", $pendaftarTotal, $teks);
        $teks = str_replace("%jumlah-tercari%", $pendaftarTercari, $teks);
        $teks = str_replace("%tanggal-sekarang%", $tanggalSekarang, $teks);

        return $teks;
    }

    /**
     * Mencari panitia pendaftaran aktif
     *
     * Mengembalikan array berisi
     * index 0: daftar id panitia aktif,
     * index 1: id ketua panitia aktif,
     * index 2: id tahun panitia aktif,
     * index 3: string tahun panitia aktif.
     *
     * @return array panitiaaktif
     */
    private function getPanitiaAktif()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $entityPanitiaAktif = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->findOneBy([
            'sekolah' => $sekolah,
            'aktif' => 1,
        ]);

        $panitia = [];
        if (is_object($entityPanitiaAktif) && $entityPanitiaAktif instanceof PanitiaPendaftaran) {
            $panitia[0] = $entityPanitiaAktif->getPanitia();
            $panitia[1] = $entityPanitiaAktif->getKetuaPanitia()->getId();
            $panitia[2] = $entityPanitiaAktif->getTahun()->getId();
            $panitia[3] = $entityPanitiaAktif->getTahun()->getTahun();
        }

        return $panitia;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.laporan.pendaftaran', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
