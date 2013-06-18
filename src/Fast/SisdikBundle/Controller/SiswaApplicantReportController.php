<?php

namespace Fast\SisdikBundle\Controller;
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
use JMS\SecurityExtraBundle\Annotation\Secure;

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
        $searchkey = '';

        $searchform = $this->createForm(new SiswaApplicantReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true
                && !($searchdata['gelombang'] instanceof Gelombang)) {
            $message = $this->get('translator')->trans('errorinfo.persentase.harus.dengan.gelombang');
            $searchform->get('jumlahBayar')->addError(new FormError($message));
        }

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2])->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorUrutPendaftaran', 'DESC');

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbAdvsearchnum = $em->createQueryBuilder()->select('COUNT(tcount.id)')
                ->from('FastSisdikBundle:Siswa', 'tcount')->setParameter('calon', true)
                ->setParameter('ortuaktif', true)->setParameter('sekolah', $sekolah->getId())
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbBiaya = $em->createQueryBuilder()->select('SUM(t.nominal)')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't');
        $biaya = 0;

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('t.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbAdvsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbBiaya->andWhere('t.gelombang = :gelombang');
                $qbBiaya->setParameter('gelombang', $searchdata['gelombang']->getId());
                $biaya = $qbBiaya->getQuery()->getSingleScalarResult();

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
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
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
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
                $querybuilder->andWhere('t.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('t.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('t.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbsearchnum->andWhere('t.jenisKelamin = :jeniskelamin');
                $qbsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbAdvsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('t4.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->andWhere('t4.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbAdvsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('t.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('t.referensi', 'ref');
                $qbsearchnum->andWhere('ref.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $qbAdvsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    if ($searchdata['jumlahBayar'] == 100 && $pembandingBayar != '<'
                            && $pembandingBayar != '<=') {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->andWhere('t.lunasBiayaPendaftaran = :lunas');
                        $querybuilder->setParameter('lunas', true);

                        $qbAdvsearchnum->setParameter('lunas', true);
                    } else {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->groupBy('t.id')
                                ->having(
                                        "SUM(t5.nominalTotal) + SUM(t5.nominalPotongan) + SUM(t5.persenPotonganDinominalkan) $pembandingBayar :jumlahbayar");
                        $querybuilder->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);

                        $qbAdvsearchnum
                                ->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);
                    }

                } else {

                    $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                    $querybuilder->groupBy('t.id')
                            ->having("SUM(t5.nominalTotal) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $qbAdvsearchnum->where($qbe->expr()->in('tcount.id', $querybuilder->getDQL()));
                $pendaftarTercari = $qbAdvsearchnum->getQuery()->getSingleScalarResult();

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
            if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $pendaftarTercari = $pendaftarTercari != 0 ? $pendaftarTercari
                : $qbsearchnum->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $summaryform = $this->createForm(new ReportSummaryType($this->container));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'panitiaAktif' => $panitiaAktif, 'pendaftarTotal' => $pendaftarTotal,
                'pendaftarTercari' => $pendaftarTercari, 'tampilkanTercari' => $tampilkanTercari,
                'pencarianLanjutan' => $pencarianLanjutan, 'searchkey' => $searchkey,
                'summaryform' => $summaryform->createView(),
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

        $searchform = $this->createForm(new SiswaApplicantReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true
                && !($searchdata['gelombang'] instanceof Gelombang)) {
            $message = $this->get('translator')->trans('errorinfo.persentase.harus.dengan.gelombang');
            $searchform->get('jumlahBayar')->addError(new FormError($message));
        }

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2])->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorUrutPendaftaran', 'DESC');

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = :tahunaktif')
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbAdvsearchnum = $em->createQueryBuilder()->select('COUNT(tcount.id)')
                ->from('FastSisdikBundle:Siswa', 'tcount')->setParameter('calon', true)
                ->setParameter('ortuaktif', true)->setParameter('sekolah', $sekolah->getId())
                ->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbBiaya = $em->createQueryBuilder()->select('SUM(t.nominal)')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't');
        $biaya = 0;

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('t.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbAdvsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbBiaya->andWhere('t.gelombang = :gelombang');
                $qbBiaya->setParameter('gelombang', $searchdata['gelombang']->getId());
                $biaya = $qbBiaya->getQuery()->getSingleScalarResult();
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
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
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
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
                $querybuilder->andWhere('t.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('t.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('t.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbsearchnum->andWhere('t.jenisKelamin = :jeniskelamin');
                $qbsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbAdvsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('t4.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->andWhere('t4.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbAdvsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('t.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('t.referensi', 'ref');
                $qbsearchnum->andWhere('ref.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $qbAdvsearchnum->setParameter('referensi', $searchdata['referensi']->getId());
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    if ($searchdata['jumlahBayar'] == 100 && $pembandingBayar != '<'
                            && $pembandingBayar != '<=') {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->andWhere('t.lunasBiayaPendaftaran = :lunas');
                        $querybuilder->setParameter('lunas', true);

                        $qbAdvsearchnum->setParameter('lunas', true);
                    } else {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->groupBy('t.id')
                                ->having(
                                        "SUM(t5.nominalTotal) + SUM(t5.nominalPotongan) + SUM(t5.persenPotonganDinominalkan) $pembandingBayar :jumlahbayar");
                        $querybuilder->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);

                        $qbAdvsearchnum
                                ->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);
                    }

                } else {

                    $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                    $querybuilder->groupBy('t.id')
                            ->having("SUM(t5.nominalTotal) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $qbAdvsearchnum->where($qbe->expr()->in('tcount.id', $querybuilder->getDQL()));
                $pendaftarTercari = $qbAdvsearchnum->getQuery()->getSingleScalarResult();
            }
        } else {
            // display error response
        }

        $pendaftarTercari = $pendaftarTercari != "" ? $pendaftarTercari
                : $qbsearchnum->getQuery()->getSingleScalarResult();

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . preg_replace('/\s+/', '', $panitiaAktif[3]) . '-'
                . date("m-d-h-i");

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir)) {
            $fs->mkdir($outputdir);
        }
        if (!$fs->exists($outputdir . $sekolah->getId())) {
            $fs->mkdir($outputdir . $sekolah->getId());
        }
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
     * @Route("/download/{filename}", name="laporan-pendaftaran_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename) {
        $sekolah = $this->isRegisteredToSchool();
        $panitiaAktif = $this->getPanitiaAktif();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $panitiaAktif[3] . '/'
                . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'File Transfer');

        $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');

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

        $qb0 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->leftJoin('t.tahun', 't2')->where('t.sekolah = :sekolah')->andWhere('t.aktif = 1')
                ->orderBy('t2.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())->setMaxResults(1);
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
