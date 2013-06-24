<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Util\Messenger;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Fast\SisdikBundle\Form\ReportSummaryType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Fast\SisdikBundle\Entity\Referensi;
use Symfony\Component\Form\FormError;
use Fast\SisdikBundle\Entity\Gelombang;
use Fast\SisdikBundle\Form\SiswaApplicantReportSearchType;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa laporan-pendaftaran controller.
 *
 * @Route("/laporan-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB')")
 */
class SiswaApplicantReportController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-pendaftaran.";
    const OUTPUTSUMMARYFILE = "ringkasan-pendaftaran.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-psb/";

    /**
     * Laporan pendaftaran siswa baru
     *
     * @Route("/", name="laporan-pendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $pencarianJumlahBayar = false;
        $searchkey = '';

        $searchform = $this->createForm(new SiswaApplicantReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2])
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->leftJoin('siswa.gelombang', 'gelombang')->leftJoin('siswa.sekolahAsal', 'sekolahasal')
                ->leftJoin('siswa.orangtuaWali', 'orangtua')->where('siswa.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('tahun.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbAdvsearchnum = $em->createQueryBuilder()->select('COUNT(tcount.id)')
                ->from('FastSisdikBundle:Siswa', 'tcount')->setParameter('calon', true)
                ->setParameter('ortuaktif', true)->setParameter('sekolah', $sekolah->getId())
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('siswa.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('siswa.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbAdvsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $qbsearchnum
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $qbsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $qbAdvsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('siswa.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbsearchnum->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $qbsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbAdvsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->andWhere('sekolahasal.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbAdvsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('siswa.referensi', 'ref');
                $qbsearchnum->andWhere('ref.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $qbAdvsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id');

                    if ($pembandingBayar == '<' || $pembandingBayar == '<='
                            || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        // masukkan pencarian untuk yg belum melakukan transaksi
                        $querybuilder
                                ->having(
                                        "(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan)) * :jumlahbayar) "
                                                . " OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0");
                    } else {
                        $querybuilder
                                ->having(
                                        "SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan)) * :jumlahbayar");
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                } else {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id')
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $qbAdvsearchnum->where($qbe->expr()->in('tcount.id', $querybuilder->getDQL()));
                $pendaftarTercari = intval($qbAdvsearchnum->getQuery()->getSingleScalarResult());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
                $pencarianJumlahBayar = true;
            }
            if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $pendaftarTercari = $pencarianJumlahBayar === true ? $pendaftarTercari
                : intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $summaryform = $this->createForm(new ReportSummaryType());

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'panitiaAktif' => $panitiaAktif, 'pendaftarTotal' => $pendaftarTotal,
                'pendaftarTercari' => $pendaftarTercari, 'tampilkanTercari' => $tampilkanTercari,
                'pencarianLanjutan' => $pencarianLanjutan, 'searchkey' => $searchkey,
                'summaryform' => $summaryform->createView(), 'searchdata' => $searchdata,
                'tanggalSekarang' => new \DateTime(),
        );
    }

    /**
     * ekspor data laporan pendaftaran siswa baru
     *
     * @Route("/export", name="laporan-pendaftaran_export")
     * @Method("POST")
     */
    public function exportAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $pencarianJumlahBayar = false;

        $searchform = $this->createForm(new SiswaApplicantReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2])
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->leftJoin('siswa.gelombang', 'gelombang')->leftJoin('siswa.sekolahAsal', 'sekolahasal')
                ->leftJoin('siswa.orangtuaWali', 'orangtua')->where('siswa.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('tahun.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbAdvsearchnum = $em->createQueryBuilder()->select('COUNT(tcount.id)')
                ->from('FastSisdikBundle:Siswa', 'tcount')->setParameter('calon', true)
                ->setParameter('ortuaktif', true)->setParameter('sekolah', $sekolah->getId())
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbBiaya = $em->createQueryBuilder()->select('SUM(biaya.nominal)')
                ->from('FastSisdikBundle:BiayaPendaftaran', 'biaya');
        $biaya = 0;

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('siswa.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('siswa.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbAdvsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbBiaya->andWhere('biaya.gelombang = :gelombang');
                $qbBiaya->setParameter('gelombang', $searchdata['gelombang']->getId());
                $biaya = $qbBiaya->getQuery()->getSingleScalarResult();
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $qbsearchnum
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $qbsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $qbAdvsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $qbAdvsearchnum->setParameter('ponselortu', "%{$searchdata['searchkey']}%");
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('siswa.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbsearchnum->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $qbsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbAdvsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->andWhere('sekolahasal.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbAdvsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('siswa.referensi', 'ref');
                $qbsearchnum->andWhere('ref.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $qbAdvsearchnum->setParameter('referensi', $searchdata['referensi']->getId());
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id')
                            ->having(
                                    "SUM(pembayaran.nominalTotalTransaksi) + SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan) $pembandingBayar "
                                            . " (SUM(siswa.sisaBiayaPendaftaran) + SUM(pembayaran.nominalTotalBiaya)) * :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                } else {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran');
                    $querybuilder->leftJoin('pembayaran.transaksiPembayaranPendaftaran', 'transaksi');
                    $querybuilder->groupBy('siswa.id')
                            ->having("SUM(transaksi.nominalPembayaran) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $qbAdvsearchnum->where($qbe->expr()->in('tcount.id', $querybuilder->getDQL()));
                $pendaftarTercari = intval($qbAdvsearchnum->getQuery()->getSingleScalarResult());

                $pencarianJumlahBayar = true;
            }
        } else {
            // TODO: display error response
        }

        $pendaftarTercari = $pencarianJumlahBayar === true ? $pendaftarTercari
                : intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . preg_replace('/\s+/', '', $panitiaAktif[3]) . '-'
                . date("m-d-h-i") . ".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir . $sekolah->getId() . '/' . $panitiaAktif[3])) {
            $fs->mkdir($outputdir . $sekolah->getId() . '/' . $panitiaAktif[3]);
        }

        $documentsource = $outputdir . $sekolah->getId() . '/' . $panitiaAktif[3] . '/' . $filesource;
        $documenttarget = $outputdir . $sekolah->getId() . '/' . $panitiaAktif[3] . '/' . $filetarget;

        $entities = $querybuilder->getQuery()->getResult();

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive
                        ->addFromString('content.xml',
                                $this
                                        ->renderView(
                                                "FastSisdikBundle:SiswaApplicantReport:report.xml.twig",
                                                array(
                                                        'entities' => $entities,
                                                        'pendaftarTercari' => $pendaftarTercari,
                                                        'pendaftarTotal' => $pendaftarTotal,
                                                )));
                if ($ziparchive->close() === TRUE) {
                    $return = array(
                            "redirectUrl" => $this
                                    ->generateUrl("laporan-pendaftaran_download",
                                            array(
                                                'filename' => $filetarget
                                            )), "filename" => $filetarget,
                    );

                    $return = json_encode($return);

                    return new Response($return, 200,
                            array(
                                'Content-Type' => 'application/json'
                            ));
                }
            }
        }
    }

    /**
     * download the generated file report
     *
     * @Route("/download/{filename}/{type}", name="laporan-pendaftaran_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods') {
        $sekolah = $this->isRegisteredToSchool();
        $panitiaAktif = $this->getPanitiaAktif();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $panitiaAktif[3] . '/'
                . $filetarget;

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
     * download the generated file report
     *
     * @Route("/ringkasan", name="laporan-pendaftaran_summary")
     * @Method("POST")
     */
    public function summaryAction() {
        $sekolah = $this->isRegisteredToSchool();
        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();

        $summaryform = $this->createForm(new ReportSummaryType());
        $summaryform->submit($this->getRequest());
        $summarydata = $summaryform->getData();

        if ($summarydata['output'] === 'sms' && $summarydata['nomorPonsel'] === null) {
            $message = $this->get('translator')->trans('errorinfo.nomor.ponsel.tak.boleh.kosong');
            $summaryform->get('nomorPonsel')->addError(new FormError($message));
        }

        if ($summaryform->isValid()) {
            if ($summarydata['output'] == 'pdf') {
                $filename = self::OUTPUTSUMMARYFILE . preg_replace('/\s+/', '', $panitiaAktif[3]) . '-'
                        . date("m-d-h-i") . ".sisdik.pdf";
                $outputdir = self::DOCUMENTS_OUTPUTDIR;

                $fs = new Filesystem();
                if (!$fs->exists($outputdir . $sekolah->getId() . '/' . $panitiaAktif[3])) {
                    $fs->mkdir($outputdir . $sekolah->getId() . '/' . $panitiaAktif[3]);
                }

                $documenttarget = $outputdir . $sekolah->getId() . '/' . $panitiaAktif[3] . '/' . $filename;

                $facade = $this->get('ps_pdf.facade');
                $tmpResponse = new Response();

                $this
                        ->render('FastSisdikBundle:SiswaApplicantReport:summary.pdf.twig',
                                array(
                                    'sekolah' => $sekolah, 'teks' => $summarydata['teksTerformat'],
                                ), $tmpResponse);
                $xml = $tmpResponse->getContent();
                $content = $facade->render($xml);

                $fp = fopen($documenttarget, "w");

                if (!$fp) {
                    throw new IOException($translator->trans("exception.open.file.pdf"));
                } else {
                    fwrite($fp, $content);
                    fclose($fp);
                }

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('laporan-pendaftaran_download',
                                                array(
                                                    'filename' => $filename, 'type' => 'pdf',
                                                )));
            } elseif ($summarydata['output'] == 'sms') {
                $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'e-laporan-ringkasan',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $nomorponsel = preg_split("/[\s,]+/", $summarydata['nomorPonsel']);
                            foreach ($nomorponsel as $ponsel) {
                                $messenger = $this->get('fast_sisdik.messenger');
                                if ($messenger instanceof Messenger) {
                                    $messenger->setPhoneNumber($ponsel);
                                    $messenger->setMessage($summarydata['teksTerformat']);
                                    $messenger->sendMessage();
                                }
                            }
                        }
                    }
                }

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.ringkasan.laporan.pendaftaran.sms.berhasil.dikirim'));
            }
        } elseif ($summarydata['output'] == 'sms' && $summarydata['nomorPonsel'] === null) {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('errorinfo.nomor.ponsel.tak.boleh.kosong'));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.ringkasan.laporan.pendaftaran.gagal.dibuat'));
        }

        return $this->redirect($this->generateUrl('laporan-pendaftaran'));
    }

    /**
     * format template
     *
     * @Route("/format", name="laporan-pendaftaran_format")
     * @Method("GET")
     */
    public function formatTemplateAction() {
        $this->isRegisteredToSchool();

        $teks = $this->getRequest()->query->get('teks');
        $teks = $this
                ->formatTemplate($teks, $this->getRequest()->query->get('gelombang'),
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
                        $this->getRequest()->query->get('tanggalSekarang'));

        $return = array(
            'teksterformat' => $teks
        );
        $return = json_encode($return);

        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * memformat teks template
     *
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
    private function formatTemplate($teks, $gelombang = '', $dariTanggal = '', $hinggaTanggal = '',
            $jenisKelamin = '', $sekolahAsal = '', $pembandingBayar = '', $jumlahBayar = '',
            $persenBayar = '', $referensi = '', $pendaftarTotal = '', $pendaftarTercari = '',
            $tanggalSekarang = '') {
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
     * Fungsi ini mengembalikan array berisi
     * index 0: daftar id panitia aktif
     * index 1: id ketua panitia aktif
     * index 2: id tahun panitia aktif
     * index 3: string tahun panitia aktif
     *
     * @return array panitiaaktif
     */
    private function getPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('panitia')
                ->from('FastSisdikBundle:PanitiaPendaftaran', 'panitia')->leftJoin('panitia.tahun', 'tahun')
                ->where('panitia.sekolah = :sekolah')->andWhere('panitia.aktif = 1')
                ->orderBy('tahun.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())
                ->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        $panitiaaktif = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $panitiaaktif[0] = $entity->getPanitia();
                $panitiaaktif[1] = $entity->getKetuaPanitia()->getId();
                $panitiaaktif[2] = $entity->getTahun()->getId();
                $panitiaaktif[3] = $entity->getTahun()->getTahun();
            }
        }

        return $panitiaaktif;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.pendaftaran']['links.laporan.pendaftaran']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
