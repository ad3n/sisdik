<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\Tahun;
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
use Fast\SisdikBundle\Form\SiswaApplicantPaymentReportSearchType;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa laporan-keuangan-pendaftaran controller.
 *
 * @Route("/laporan-keuangan-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class SiswaApplicantPaymentReportController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-keuangan-pendaftaran.";
    const OUTPUTSUMMARYFILE = "ringkasan-keuangan-pendaftaran.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-keuangan-psb/";

    /**
     * Laporan keuangan pendaftaran siswa baru
     *
     * @Route("/", name="laporan-keuangan-pendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $transaksiTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $pencarianJumlahBayar = false;
        $searchkey = '';

        $searchform = $this->createForm(new SiswaApplicantPaymentReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('transaksi.id'))
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
        $transaksiTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('transaksi.id'))
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->leftJoin('pembayaran.siswa', 'siswa');

        $querybuilder = $em->createQueryBuilder()->select('transaksi')
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->leftJoin('pembayaran.siswa', 'siswa')->orderBy('transaksi.waktuSimpan', 'DESC');

        if ($searchform->isValid()) {

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'transaksi.nomorTransaksi LIKE :nomortransaksi '
                                        . ' OR transaksi.keterangan LIKE :keterangan ');
                $querybuilder->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");

                $qbsearchnum
                        ->andWhere(
                                'transaksi.nomorTransaksi LIKE :nomortransaksi '
                                        . ' OR transaksi.keterangan LIKE :keterangan ');
                $qbsearchnum->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('transaksi.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('transaksi.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('transaksi.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('transaksi.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->leftJoin('siswa.sekolahAsal', 'sekolahasal');
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->leftJoin('siswa.sekolahAsal', 'sekolahasal');
                $qbsearchnum->andWhere('sekolahasal.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'referensi');
                $querybuilder->andWhere('referensi.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('siswa.referensi', 'referensi');
                $qbsearchnum->andWhere('referensi.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                $querybuilder->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar");
                $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                $qbsearchnum->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar");
                $qbsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
                $pencarianJumlahBayar = true;
            }

        } else {
            $pencarianLanjutan = true;
        }

        $transaksiTercari = $pencarianJumlahBayar === true ? $transaksiTercari
                : intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $summaryform = $this->createForm(new ReportSummaryType());

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'transaksiTotal' => $transaksiTotal, 'transaksiTercari' => $transaksiTercari,
                'tampilkanTercari' => $tampilkanTercari, 'pencarianLanjutan' => $pencarianLanjutan,
                'searchkey' => $searchkey, 'summaryform' => $summaryform->createView(),
                'searchdata' => $searchdata, 'tanggalSekarang' => new \DateTime(),
        );
    }

    /**
     * ekspor data laporan keuangan pendaftaran siswa baru
     *
     * @Route("/export", name="laporan-keuangan-pendaftaran_export")
     * @Method("POST")
     */
    public function exportAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $transaksiTercari = 0;
        $pencarianJumlahBayar = false;

        $searchform = $this->createForm(new SiswaApplicantPaymentReportSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('transaksi.id'))
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
        $transaksiTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('transaksi.id'))
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->leftJoin('pembayaran.siswa', 'siswa');

        $querybuilder = $em->createQueryBuilder()->select('transaksi')
                ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
                ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->leftJoin('pembayaran.siswa', 'siswa')->orderBy('transaksi.waktuSimpan', 'DESC');

        if ($searchform->isValid()) {

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'transaksi.nomorTransaksi LIKE :nomortransaksi '
                                        . ' OR transaksi.keterangan LIKE :keterangan ');
                $querybuilder->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");

                $qbsearchnum
                        ->andWhere(
                                'transaksi.nomorTransaksi LIKE :nomortransaksi '
                                        . ' OR transaksi.keterangan LIKE :keterangan ');
                $qbsearchnum->setParameter('nomortransaksi', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('transaksi.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('transaksi.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('transaksi.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('transaksi.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->leftJoin('siswa.sekolahAsal', 'sekolahasal');
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->leftJoin('siswa.sekolahAsal', 'sekolahasal');
                $qbsearchnum->andWhere('sekolahasal.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'referensi');
                $querybuilder->andWhere('referensi.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $qbsearchnum->leftJoin('siswa.referensi', 'referensi');
                $qbsearchnum->andWhere('referensi.id = :referensi');
                $qbsearchnum->setParameter('referensi', $searchdata['referensi']->getId());
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                $querybuilder->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar");
                $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                $qbsearchnum->andWhere("transaksi.nominalPembayaran $pembandingBayar :jumlahbayar");
                $qbsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                $pencarianJumlahBayar = true;
            }

        } else {
            // TODO error response
        }

        $transaksiTercari = $pencarianJumlahBayar === true ? $transaksiTercari
                : intval($qbsearchnum->getQuery()->getSingleScalarResult());

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . '-' . date("m-d-h-i") . ".sisdik";

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
                $ziparchive
                        ->addFromString('content.xml',
                                $this
                                        ->renderView(
                                                "FastSisdikBundle:SiswaApplicantPaymentReport:report.xml.twig",
                                                array(
                                                        'entities' => $entities,
                                                        'transaksiTercari' => $transaksiTercari,
                                                        'transaksiTotal' => $transaksiTotal,
                                                )));
                if ($ziparchive->close() === TRUE) {
                    $return = array(
                            "redirectUrl" => $this
                                    ->generateUrl("laporan-keuangan-pendaftaran_download",
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
     * @Route("/download/{filename}/{type}", name="laporan-keuangan-pendaftaran_download")
     * @Method("GET")
     */
    public function downloadReportFileAction($filename, $type = 'ods') {
        $sekolah = $this->isRegisteredToSchool();

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

    /**
     * download the generated file report
     *
     * @Route("/ringkasan", name="laporan-keuangan-pendaftaran_summary")
     * @Method("POST")
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
                $filename = self::OUTPUTSUMMARYFILE . '-' . date("m-d-h-i") . ".sisdik.pdf";
                $outputdir = self::DOCUMENTS_OUTPUTDIR;

                $fs = new Filesystem();
                if (!$fs->exists($outputdir . $sekolah->getId() . '/')) {
                    $fs->mkdir($outputdir . $sekolah->getId() . '/');
                }

                $documenttarget = $outputdir . $sekolah->getId() . '/' . $filename;

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
                                        ->generateUrl('laporan-keuangan-pendaftaran_download',
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

        return $this->redirect($this->generateUrl('laporan-keuangan-pendaftaran'));
    }

    /**
     * format template
     *
     * @Route("/format", name="laporan-keuangan-pendaftaran_format")
     * @Method("GET")
     */
    public function formatTemplateAction() {
        $this->isRegisteredToSchool();

        $teks = $this->getRequest()->query->get('teks');
        $teks = $this
                ->formatTemplate($teks, $this->getRequest()->query->get('gelombang'),
                        $this->getRequest()->query->get('dariTanggal'),
                        $this->getRequest()->query->get('hinggaTanggal'),
                        $this->getRequest()->query->get('sekolahAsal'),
                        $this->getRequest()->query->get('pembandingBayar'),
                        $this->getRequest()->query->get('jumlahBayar'),
                        $this->getRequest()->query->get('persenBayar'),
                        $this->getRequest()->query->get('referensi'),
                        $this->getRequest()->query->get('transaksiTotal'),
                        $this->getRequest()->query->get('transaksiTercari'),
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
     * @param  string $sekolahAsal
     * @param  string $pembandingBayar
     * @param  string $jumlahBayar
     * @param  string $persenBayar
     * @param  string $referensi
     * @param  string $transaksiTotal
     * @param  string $transaksiTercari
     * @param  string $tanggalSekarang
     * @return string $teks
     */
    private function formatTemplate($teks, $gelombang = '', $dariTanggal = '', $hinggaTanggal = '',
            $sekolahAsal = '', $pembandingBayar = '', $jumlahBayar = '', $persenBayar = '', $referensi = '',
            $transaksiTotal = '', $transaksiTercari = '', $tanggalSekarang = '') {
        $teks = str_replace("%gelombang%", $gelombang, $teks);
        $teks = str_replace("%dari-tanggal%", $dariTanggal, $teks);
        $teks = str_replace("%hingga-tanggal%", $hinggaTanggal, $teks);
        $teks = str_replace("%sekolah-asal%", $sekolahAsal, $teks);

        $teks = str_replace("%pembanding-bayar%", $pembandingBayar, $teks);
        $teks = str_replace("%jumlah-bayar%", $jumlahBayar, $teks);
        $teks = str_replace("%persen-bayar%", ($persenBayar == '1') ? '%' : '', $teks);

        $teks = str_replace("%perujuk%", $referensi, $teks);
        $teks = str_replace("%jumlah-total%", $transaksiTotal, $teks);
        $teks = str_replace("%jumlah-tercari%", $transaksiTercari, $teks);
        $teks = str_replace("%tanggal-sekarang%", $tanggalSekarang, $teks);

        return $teks;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.laporan.keuangan.pendaftaran']->setCurrent(true);
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
