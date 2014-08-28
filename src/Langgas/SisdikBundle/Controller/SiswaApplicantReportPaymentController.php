<?php

namespace Langgas\SisdikBundle\Controller;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Util\Messenger;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Langgas\SisdikBundle\Form\ReportSummaryType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Langgas\SisdikBundle\Entity\Referensi;
use Symfony\Component\Form\FormError;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Form\SiswaApplicantReportPaymentSearchType;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Siswa laporan-pembayaran-pendaftaran controller.
 *
 * @Route("/laporan-pembayaran-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class SiswaApplicantReportPaymentController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-pembayaran-pendaftaran.";
    const OUTPUTSUMMARYFILE = "ringkasan-pembayaran-pendaftaran.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-pembayaran-psb/";

    /**
     * Laporan pembayaran pendaftaran siswa baru
     *
     * @Route("/", name="laporan-pembayaran-pendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm(new SiswaApplicantReportPaymentSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('LanggasSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        if ($searchform->isValid()) {

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder->andWhere('siswa.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());

                $tampilkanTercari = true;
            }

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('siswa.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

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

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"));

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

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
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                                . " OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0");
                    } else {
                        $querybuilder
                                ->having(
                                        "SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar");
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                } else {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id')
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

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

        $pendaftarTercari = count($querybuilder->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        $summaryform = $this->createForm(new ReportSummaryType());

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'pendaftarTotal' => $pendaftarTotal, 'pendaftarTercari' => $pendaftarTercari,
                'tampilkanTercari' => $tampilkanTercari, 'pencarianLanjutan' => $pencarianLanjutan,
                'searchkey' => $searchkey, 'summaryform' => $summaryform->createView(),
                'searchdata' => $searchdata, 'tanggalSekarang' => new \DateTime(),
        );
    }

    /**
     * ekspor data laporan pendaftaran siswa baru
     *
     * @Route("/export", name="laporan-pembayaran-pendaftaran_export")
     * @Method("POST")
     */
    public function exportAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $judulLaporan = $this->get('translator')->trans('heading.laporan.pembayaran.pendaftaran');

        $searchform = $this->createForm(new SiswaApplicantReportPaymentSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('LanggasSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->where('siswa.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        if ($searchform->isValid()) {

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder->andWhere('siswa.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());

                $judulLaporan .= " " . $this->get('translator')->trans('tahun') . " "
                        . $searchdata['tahun']->getTahun();
            }

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('siswa.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $judulLaporan .= " " . $searchdata['gelombang']->getNama();
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

                $judulLaporan .= ", " . $this->get('translator')->trans('kata.pencarian') . " " . $searchkey;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"));

                $judulLaporan .= ", " . $this->get('translator')->trans('dari.tanggal') . " "
                        . $dariTanggal->format("Y-m-d 00:00:00");
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"));

                $judulLaporan .= ", " . $this->get('translator')->trans('hingga.tanggal') . " "
                        . $hinggaTanggal->format("Y-m-d 24:00:00");
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $judulLaporan .= ", " . $this->get('translator')->trans('jenis.kelamin') . " "
                        . ($searchdata['jenisKelamin'] == 'L' ? 'Laki-laki' : 'Perempuan');
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $judulLaporan .= ", " . $this->get('translator')->trans('sekolah.asal') . " "
                        . $searchdata['sekolahAsal']->getNama();
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $judulLaporan .= ", " . $this->get('translator')->trans('referensi') . " "
                        . $searchdata['referensi']->getNama();
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
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                                . " OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0");
                    } else {
                        $querybuilder
                                ->having(
                                        "SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar");
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                } else {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id')
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
        } else {
            // TODO: display error response
        }

        $entities = $querybuilder->getQuery()->getResult();
        $pendaftarTercari = count($entities);

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
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

        $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
                ->findBy(
                        array(
                                'tahun' => $searchdata['tahun']->getId(),
                                'gelombang' => $searchdata['gelombang']->getId(),
                        ), array(
                            'urutan' => 'ASC'
                        ));

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive
                        ->addFromString('styles.xml',
                                $this
                                        ->renderView(
                                                "LanggasSisdikBundle:SiswaApplicantReportPayment:styles.xml.twig"));
                $ziparchive
                        ->addFromString('content.xml',
                                $this
                                        ->renderView(
                                                "LanggasSisdikBundle:SiswaApplicantReportPayment:report.xml.twig",
                                                array(
                                                        'entities' => $entities,
                                                        'pendaftarTercari' => $pendaftarTercari,
                                                        'pendaftarTotal' => $pendaftarTotal,
                                                        'judulLaporan' => $judulLaporan,
                                                        'biayaPendaftaran' => $biayaPendaftaran,
                                                        'akhirKolomBiaya' => $this
                                                                ->num2alpha(count($biayaPendaftaran) + 3),
                                                )));
                if ($ziparchive->close() === TRUE) {
                    $return = array(
                            "redirectUrl" => $this
                                    ->generateUrl("laporan-pembayaran-pendaftaran_download",
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
     * @Route("/download/{filename}/{type}", name="laporan-pembayaran-pendaftaran_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods') {
        $sekolah = $this->isRegisteredToSchool();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $filetarget;

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
     * pembuat ringkasan
     *
     * @Route("/ringkasan", name="laporan-pembayaran-pendaftaran_summary")
     * @Method("POST")
     * @Secure(roles="ROLE_KETUA_PANITIA_PSB")
     */
    public function summaryAction() {
        $sekolah = $this->isRegisteredToSchool();

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
                $filename = self::OUTPUTSUMMARYFILE . date("Y-m-d-h-i") . ".sisdik.pdf";
                $outputdir = self::DOCUMENTS_OUTPUTDIR;

                $fs = new Filesystem();
                if (!$fs->exists($outputdir . $sekolah->getId() . '/')) {
                    $fs->mkdir($outputdir . $sekolah->getId() . '/');
                }

                $documenttarget = $outputdir . $sekolah->getId() . '/' . $filename;

                $facade = $this->get('ps_pdf.facade');
                $tmpResponse = new Response();

                $this
                        ->render('LanggasSisdikBundle:SiswaApplicantReport:summary.pdf.twig',
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
                                        ->generateUrl('laporan-pembayaran-pendaftaran_download',
                                                array(
                                                    'filename' => $filename, 'type' => 'pdf',
                                                )));
            } elseif ($summarydata['output'] == 'sms') {
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'e-laporan-ringkasan',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $nomorponsel = preg_split("/[\s,\/]+/", $summarydata['nomorPonsel']);
                            foreach ($nomorponsel as $ponsel) {
                                $messenger = $this->get('sisdik.messenger');
                                if ($messenger instanceof Messenger) {
                                    $messenger->setPhoneNumber($ponsel);
                                    $messenger->setMessage($summarydata['teksTerformat']);
                                    $messenger->sendMessage($sekolah);
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

        return $this->redirect($this->generateUrl('laporan-pembayaran-pendaftaran'));
    }

    /**
     * format template
     *
     * @Route("/format", name="laporan-pembayaran-pendaftaran_format")
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
     * Mengambil identitas biaya pendaftaran seorang siswa
     *
     * @param Siswa $siswa
     * @return
     * array['semua'] array id biaya pendaftaran seluruhnya<br>
     * array['tersimpan'] array id biaya pendaftaran tersimpan<br>
     * array['tersisa'] array id biaya pendaftaran tersisa<br>
     *
     */
    private function getBiayaProperties(Siswa $siswa) {
        $em = $this->getDoctrine()->getManager();

        $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
                ->findBy(
                        array(
                            'tahun' => $siswa->getTahun(), 'gelombang' => $siswa->getGelombang(),
                        ), array(
                            'urutan' => 'ASC'
                        ));
        $idBiayaSemua = array();
        foreach ($biayaPendaftaran as $biaya) {
            if ($biaya instanceof BiayaPendaftaran) {
                $idBiayaSemua[] = $biaya->getId();
            }
        }

        $querybuilder1 = $em->createQueryBuilder()->select('daftar')
                ->from('LanggasSisdikBundle:DaftarBiayaPendaftaran', 'daftar')
                ->leftJoin('daftar.biayaPendaftaran', 'biaya')
                ->leftJoin('daftar.pembayaranPendaftaran', 'pembayaran')->where('pembayaran.siswa = :siswa')
                ->setParameter('siswa', $siswa->getId())->orderBy('biaya.urutan', 'ASC');
        $daftarBiaya = $querybuilder1->getQuery()->getResult();
        $idBiayaTersimpan = array();
        foreach ($daftarBiaya as $daftar) {
            if ($daftar instanceof DaftarBiayaPendaftaran) {
                $idBiayaTersimpan[] = $daftar->getBiayaPendaftaran()->getId();
            }
        }

        $idBiayaSisa = array_diff($idBiayaSemua, $idBiayaTersimpan);

        return array(
            'semua' => $idBiayaSemua, 'tersimpan' => $idBiayaTersimpan, 'tersisa' => $idBiayaSisa,
        );
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
     * Converts an integer into the alphabet base (A-Z).
     *
     * @param int $n This is the number to convert.
     * @return string The converted number.
     * @author Theriault
     *
     */
    private function num2alpha($n) {
        $r = '';
        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
            $n -= pow(26, $i);
        }
        return $r;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.payments', array(), 'navigations')][$this->get('translator')->trans('links.laporan.pembayaran.pendaftaran', array(), 'navigations')]->setCurrent(true);
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
