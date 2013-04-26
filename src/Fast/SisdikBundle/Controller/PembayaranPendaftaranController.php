<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Util\Messenger;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Entity\LayananSmsPendaftaran;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Component\Form\FormError;
use Symfony\Component\Filesystem\Exception\IOException;
use Fast\SisdikBundle\Util\EscapeCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PembayaranPendaftaran;
use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;
use Fast\SisdikBundle\Form\PembayaranPendaftaranType;
use Fast\SisdikBundle\Form\PembayaranPendaftaranCicilanType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * PembayaranPendaftaran controller.
 *
 * @Route("/payment/registrationfee/{sid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KETUA_PANITIA_PSB')")
 */
class PembayaranPendaftaranController extends Controller
{
    const RECEIPTS_DIR = "/receipts/";

    /**
     * Lists all PembayaranPendaftaran entities.
     *
     * @Route("/", name="payment_registrationfee")
     * @Template()
     */
    public function indexAction($sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $jumlahItemBiaya = count(
                $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                        ->findBy(
                                array(
                                    'tahun' => $siswa->getTahun(), 'gelombang' => $siswa->getGelombang(),
                                ),
                                array(
                                    'urutan' => 'ASC'
                                )));

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $totalBiaya = array();
        $totalPotongan = 0;
        $adaPotongan = array();
        $totalAdaPotongan = false;
        $editLink = array();
        $biayaTerbayar = array();
        $jumlahItemBiayaTerbayar = 0;
        foreach ($entities as $pembayaran) {
            if (is_object($pembayaran) && $pembayaran instanceof PembayaranPendaftaran) {
                $nominalBiaya = 0;
                $jumlahItemBiayaTerbayar += count($pembayaran->getDaftarBiayaPendaftaran());
                foreach ($pembayaran->getDaftarBiayaPendaftaran() as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    $nominalBiaya += $biayaPendaftaran->getNominal();
                }

                $adaPotongan[] = $pembayaran->getAdaPotongan();
                if ($pembayaran->getAdaPotongan()) {
                    $totalAdaPotongan = true;
                    $jenisPotongan = $pembayaran->getJenisPotongan();
                    if ($jenisPotongan == 'nominal') {
                        $nominalBiayaTerpotong = $nominalBiaya - $pembayaran->getNominalPotongan();
                        $totalPotongan += $pembayaran->getNominalPotongan();
                    } else if ($jenisPotongan == 'persentase') {
                        $nominalBiayaTerpotong = $nominalBiaya
                                - ($nominalBiaya * ($pembayaran->getPersenPotongan() / 100));
                        $totalPotongan += $nominalBiaya * ($pembayaran->getPersenPotongan() / 100);
                    }
                    $totalBiaya[] = $nominalBiayaTerpotong;

                    if ($pembayaran->getTotalNominalTransaksiPembayaranPendaftaran()
                            == $nominalBiayaTerpotong) {
                        $editLink[] = false;
                    } else {
                        $editLink[] = true;
                    }
                } else {
                    $totalBiaya[] = $nominalBiaya;
                    if ($pembayaran->getTotalNominalTransaksiPembayaranPendaftaran() == $nominalBiaya
                            && $nominalBiaya != 0) {
                        $editLink[] = false;
                    } else {
                        $editLink[] = true;
                    }
                }

                unset($tmp);
                $tmp = array_merge($pembayaran->getDaftarBiayaPendaftaran(), $biayaTerbayar);

                unset($biayaTerbayar);
                $biayaTerbayar = $tmp;
            }
        }

        if ($jumlahItemBiaya == $jumlahItemBiayaTerbayar && $jumlahItemBiayaTerbayar > 0) {
            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahItemBiaya' => $jumlahItemBiaya,
                    'jumlahItemBiayaTerbayar' => $jumlahItemBiayaTerbayar, 'totalBiaya' => $totalBiaya,
                    'editLink' => $editLink, 'adaPotongan' => $adaPotongan,
                    'totalPotongan' => $totalPotongan, 'totalAdaPotongan' => $totalAdaPotongan,
            );
        } else {
            $entity = new PembayaranPendaftaran();
            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);

            $form = $this
                    ->createForm(new PembayaranPendaftaranType($this->container, $sid, $biayaTerbayar),
                            $entity);

            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahItemBiaya' => $jumlahItemBiaya,
                    'jumlahItemBiayaTerbayar' => $jumlahItemBiayaTerbayar, 'form' => $form->createView(),
                    'totalBiaya' => $totalBiaya, 'editLink' => $editLink, 'adaPotongan' => $adaPotongan,
                    'totalPotongan' => $totalPotongan, 'totalAdaPotongan' => $totalAdaPotongan,
            );
        }
    }

    /**
     * Creates a new PembayaranPendaftaran entity.
     *
     * @Route("/create", name="payment_registrationfee_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PembayaranPendaftaran:index.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $jumlahItemBiaya = count(
                $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                        ->findBy(
                                array(
                                    'tahun' => $siswa->getTahun(), 'gelombang' => $siswa->getGelombang(),
                                )));

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $totalBiaya = array();
        $totalPotongan = 0;
        $adaPotongan = array();
        $totalAdaPotongan = false;
        $editLink = array();
        $biayaTerbayar = array();
        $jumlahItemBiayaTerbayar = 0;
        foreach ($entities as $pembayaran) {
            if (is_object($pembayaran) && $pembayaran instanceof PembayaranPendaftaran) {
                $nominalBiaya = 0;
                $jumlahItemBiayaTerbayar += count($pembayaran->getDaftarBiayaPendaftaran());
                foreach ($pembayaran->getDaftarBiayaPendaftaran() as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    $nominalBiaya += $biayaPendaftaran->getNominal();
                }

                $adaPotongan[] = $pembayaran->getAdaPotongan();
                if ($pembayaran->getAdaPotongan()) {
                    $totalAdaPotongan = true;
                    $jenisPotongan = $pembayaran->getJenisPotongan();
                    if ($jenisPotongan == 'nominal') {
                        $nominalBiayaTerpotong = $nominalBiaya - $pembayaran->getNominalPotongan();
                        $totalPotongan += $pembayaran->getNominalPotongan();
                    } else if ($jenisPotongan == 'persentase') {
                        $nominalBiayaTerpotong = $nominalBiaya
                                - ($nominalBiaya * ($pembayaran->getPersenPotongan() / 100));
                        $totalPotongan += $nominalBiaya * ($pembayaran->getPersenPotongan() / 100);
                    }
                    $totalBiaya[] = $nominalBiayaTerpotong;

                    if ($pembayaran->getTotalNominalTransaksiPembayaranPendaftaran()
                            == $nominalBiayaTerpotong && $nominalBiayaTerpotong != 0) {
                        $editLink[] = false;
                    } else {
                        $editLink[] = true;
                    }
                } else {
                    $totalBiaya[] = $nominalBiaya;
                    if ($pembayaran->getTotalNominalTransaksiPembayaranPendaftaran() == $nominalBiaya
                            && $nominalBiaya != 0) {
                        $editLink[] = false;
                    } else {
                        $editLink[] = true;
                    }
                }

                unset($tmp);
                $tmp = array_merge($pembayaran->getDaftarBiayaPendaftaran(), $biayaTerbayar);

                unset($biayaTerbayar);
                $biayaTerbayar = $tmp;
            }
        }

        $entity = new PembayaranPendaftaran();
        $form = $this
                ->createForm(new PembayaranPendaftaranType($this->container, $sid, $biayaTerbayar), $entity);
        $form->bind($request);

        // periksa jika item pembayaran sudah digunakan sebelumnya
        $formdata = $form->getData();
        if (count($formdata->getDaftarBiayaPendaftaran()) <= 0) {
            $message = $this->get('translator')->trans('alert.registrationfee.is.inserted');
            $form->get('daftarBiayaPendaftaran')->addError(new FormError($message));
        }

        if ($form->isValid()) {

            $entity->setSiswa($siswa);
            foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                $transaksi->setDibuatOleh($this->getUser());
                $currentPaymentAmount = $transaksi->getNominalPembayaran();
            }

            if ($entity->getAdaPotongan() && $entity->getPersenPotongan() != 0) {
                $nominal = 0;
                foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    $nominal += $biayaPendaftaran->getNominal();
                }
                $persenPotonganDinominalkan = $nominal * ($entity->getPersenPotongan() / 100);
                $entity->setPersenPotonganDinominalkan($persenPotonganDinominalkan);
            }
            $currentDiscount = $entity->getNominalPotongan() + $entity->getPersenPotonganDinominalkan();

            $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran() + $currentPaymentAmount;
            $payableAmount = $this
                    ->getPayableRegistrationFees($siswa->getTahun()->getId(), $siswa->getGelombang()->getId());
            $totalDiscount = $siswa->getTotalPotonganPembayaranPendaftaran() + $currentDiscount;
            $payableAmountDiscounted = $payableAmount - $totalDiscount;

            if ($totalPayment == $payableAmountDiscounted) {
                $siswa->setLunasBiayaPendaftaran(true);
            }

            // print("\$totalPayment: $totalPayment<br />");
            // print("\$payableAmount: $payableAmount<br />");
            // print("\$totalDiscount: $totalDiscount<br />");
            // print("\$payableAmountDiscounted: $payableAmountDiscounted<br />");
            // exit;

            $em->persist($entity);
            $em->persist($siswa);

            foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                $biayaPendaftaran->setTerpakai(true);

                $em->persist($biayaPendaftaran);
            }
            $em->flush();

            if ($jumlahItemBiayaTerbayar == 0) {
                $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'b-pendaftaran-bayar-pertama',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em
                                    ->getRepository('FastSisdikBundle:LayananSmsPendaftaran')
                                    ->findBy(
                                            array(
                                                    'sekolah' => $sekolah,
                                                    'jenisLayanan' => 'b-pendaftaran-bayar-pertama'
                                            ));
                            foreach ($layananSmsPendaftaran as $layanan) {
                                if ($layanan instanceof LayananSmsPendaftaran) {
                                    $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                    $namaOrtuWali = "";
                                    $ponselOrtuWali = "";
                                    foreach ($siswa->getOrangtuaWali() as $orangtuaWali) {
                                        if ($orangtuaWali instanceof OrangtuaWali) {
                                            if ($orangtuaWali->isAktif()) {
                                                $namaOrtuWali = $orangtuaWali->getNama();
                                                $ponselOrtuWali = $orangtuaWali->getPonsel();
                                                break;
                                            }
                                        }
                                    }

                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(),
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%nomor-pendaftaran%",
                                            $siswa->getNomorPendaftaran(), $tekstemplate);
                                    $tekstemplate = str_replace("%tahun%", $siswa->getTahun()->getTahun(),
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%gelombang%",
                                            $siswa->getGelombang()->getNama(), $tekstemplate);

                                    if ($ponselOrtuWali != "") {
                                        $messenger = $this->get('fast_sisdik.messenger');
                                        if ($messenger instanceof Messenger) {
                                            $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                            foreach ($nomorponsel as $ponsel) {

                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);

                                                $logid = $messenger->setLogEntry();
                                                // $logid = 0; // for debugging

                                                $dlrurl = "http://" . $this->getRequest()->getHost()
                                                        . $this
                                                                ->generateUrl(
                                                                        "localapi_logsmskeluar_dlr_update",
                                                                        array(
                                                                                'logid' => $logid,
                                                                                'status' => "%d",
                                                                                'time' => "%T"
                                                                        ));
                                                $messenger->setDeliveryReportURL(urldecode($dlrurl));

                                                $messenger->sendMessage();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                    ->findBy(
                            array(
                                'sekolah' => $sekolah, 'jenisLayanan' => 'c-pendaftaran-bayar',
                            ));

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSmsPendaftaran = $em->getRepository('FastSisdikBundle:LayananSmsPendaftaran')
                                ->findBy(
                                        array(
                                            'sekolah' => $sekolah, 'jenisLayanan' => 'c-pendaftaran-bayar'
                                        ));
                        foreach ($layananSmsPendaftaran as $layanan) {
                            if ($layanan instanceof LayananSmsPendaftaran) {
                                $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                $namaOrtuWali = "";
                                $ponselOrtuWali = "";
                                foreach ($siswa->getOrangtuaWali() as $orangtuaWali) {
                                    if ($orangtuaWali instanceof OrangtuaWali) {
                                        if ($orangtuaWali->isAktif()) {
                                            $namaOrtuWali = $orangtuaWali->getNama();
                                            $ponselOrtuWali = $orangtuaWali->getPonsel();
                                            break;
                                        }
                                    }
                                }

                                $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(),
                                        $tekstemplate);

                                $nomorTransaksi = "";
                                $em->refresh($entity);
                                foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                                    if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                                        $em->refresh($transaksi);
                                        $nomorTransaksi = $transaksi->getNomorTransaksi();
                                    }
                                }
                                $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi,
                                        $tekstemplate);

                                $counter = 1;
                                $daftarBiayaPendaftaranDibayar = array();
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biayaPendaftaran) {
                                    $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                                            ->find($biayaPendaftaran);
                                    if ($counter > 3) {
                                        $daftarBiayaPendaftaranDibayar[] = $this->get('translator')
                                                ->trans('dll');
                                        break;
                                    }
                                    $daftarBiayaPendaftaranDibayar[] = $biaya->getJenisbiaya()->getNama();
                                    $counter++;
                                }
                                $tekstemplate = str_replace("%daftar-biaya%",
                                        (implode(", ", $daftarBiayaPendaftaranDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'),
                                        \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace("%besar-pembayaran%",
                                        $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                        $tekstemplate);

                                if ($ponselOrtuWali != "") {
                                    $messenger = $this->get('fast_sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {

                                            $messenger->setPhoneNumber($ponsel);
                                            $messenger->setMessage($tekstemplate);

                                            $logid = $messenger->setLogEntry();
                                            // $logid = 0; // for debugging

                                            $dlrurl = "http://" . $this->getRequest()->getHost()
                                                    . $this
                                                            ->generateUrl(
                                                                    "localapi_logsmskeluar_dlr_update",
                                                                    array(
                                                                            'logid' => $logid,
                                                                            'status' => "%d", 'time' => "%T"
                                                                    ));
                                            $messenger->setDeliveryReportURL(urldecode($dlrurl));

                                            $messenger->sendMessage();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($totalPayment == $payableAmountDiscounted) {
                $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em
                                    ->getRepository('FastSisdikBundle:LayananSmsPendaftaran')
                                    ->findBy(
                                            array(
                                                    'sekolah' => $sekolah,
                                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas'
                                            ));
                            foreach ($layananSmsPendaftaran as $layanan) {
                                if ($layanan instanceof LayananSmsPendaftaran) {
                                    $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                    $namaOrtuWali = "";
                                    $ponselOrtuWali = "";
                                    foreach ($siswa->getOrangtuaWali() as $orangtuaWali) {
                                        if ($orangtuaWali instanceof OrangtuaWali) {
                                            if ($orangtuaWali->isAktif()) {
                                                $namaOrtuWali = $orangtuaWali->getNama();
                                                $ponselOrtuWali = $orangtuaWali->getPonsel();
                                                break;
                                            }
                                        }
                                    }

                                    $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali,
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(),
                                            $tekstemplate);

                                    $formatter = new \NumberFormatter(
                                            $this->container->getParameter('locale'),
                                            \NumberFormatter::CURRENCY);
                                    $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                    $tekstemplate = str_replace("%total-pembayaran%",
                                            $symbol . ". " . number_format($totalPayment, 0, ',', '.'),
                                            $tekstemplate);

                                    if ($ponselOrtuWali != "") {
                                        $messenger = $this->get('fast_sisdik.messenger');
                                        if ($messenger instanceof Messenger) {
                                            $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                            foreach ($nomorponsel as $ponsel) {

                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);

                                                $logid = $messenger->setLogEntry();
                                                // $logid = 0; // for debugging

                                                $dlrurl = "http://" . $this->getRequest()->getHost()
                                                        . $this
                                                                ->generateUrl(
                                                                        "localapi_logsmskeluar_dlr_update",
                                                                        array(
                                                                                'logid' => $logid,
                                                                                'status' => "%d",
                                                                                'time' => "%T"
                                                                        ));
                                                $messenger->setDeliveryReportURL(urldecode($dlrurl));

                                                $messenger->sendMessage();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.payment.registration.inserted'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_registrationfee',
                                            array(
                                                'sid' => $sid,
                                            )));
        }

        $this->get('session')->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.payment.registration.fail.insert'));

        return array(
                'entities' => $entities, 'siswa' => $siswa, 'jumlahItemBiaya' => $jumlahItemBiaya,
                'jumlahItemBiayaTerbayar' => $jumlahItemBiayaTerbayar, 'form' => $form->createView(),
                'totalBiaya' => $totalBiaya, 'editLink' => $editLink, 'adaPotongan' => $adaPotongan,
                'totalPotongan' => $totalPotongan, 'totalAdaPotongan' => $totalAdaPotongan,
        );
    }

    /**
     * Get payable registration fee amount
     *
     */
    private function getPayableRegistrationFees($tahun, $gelombang) {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                ->findBy(
                        array(
                            'tahun' => $tahun, 'gelombang' => $gelombang
                        ));

        $feeamount = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $feeamount += $entity->getNominal();
            }
        }

        return $feeamount;
    }

    /**
     * Finds and displays a PembayaranPendaftaran entity.
     *
     * @Route("/{id}/show", name="payment_registrationfee_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        $nominalBiaya = 0;
        foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
            $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
            $nominalBiaya += $biayaPendaftaran->getNominal();
        }
        $adaPotongan = $entity->getAdaPotongan();
        $jenisPotongan = "";
        $nominalPotongan = 0;
        $persenPotongan = 0;
        if ($adaPotongan) {
            $jenisPotongan = $entity->getJenisPotongan();
            if ($jenisPotongan == 'nominal') {
                $nominalPotongan = $entity->getNominalPotongan();
            } else if ($jenisPotongan == 'persentase') {
                $nominalPotongan = $nominalBiaya * ($entity->getPersenPotongan() / 100);
                $persenPotongan = $entity->getPersenPotongan();
            }
        }

        $transaksiPembayaran = $em->getRepository('FastSisdikBundle:TransaksiPembayaranPendaftaran')
                ->findBy(
                        array(
                            'pembayaranPendaftaran' => $id
                        ),
                        array(
                            'waktuSimpan' => 'ASC'
                        ));

        return array(
                'siswa' => $siswa, 'entity' => $entity,
                'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                'transaksiPembayaran' => $transaksiPembayaran, 'nominalBiaya' => $nominalBiaya,
                'adaPotongan' => $adaPotongan, 'jenisPotongan' => $jenisPotongan,
                'nominalPotongan' => $nominalPotongan, 'persenPotongan' => $persenPotongan,
        );
    }

    /**
     * Mengatur cicilan pembayaran biaya pendaftaran
     *
     * @Route("/{id}/edit", name="payment_registrationfee_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($daftarBiayaPendaftaran[0]);

        $nominalBiaya = $biaya->getNominal();
        $adaPotongan = $entity->getAdaPotongan();
        $jenisPotongan = "";
        $nominalPotongan = 0;
        $persenPotongan = 0;
        if ($adaPotongan) {
            $jenisPotongan = $entity->getJenisPotongan();
            if ($jenisPotongan == 'nominal') {
                $nominalPotongan = $entity->getNominalPotongan();
            } else if ($jenisPotongan == 'persentase') {
                $nominalPotongan = $nominalBiaya * ($entity->getPersenPotongan() / 100);
                $persenPotongan = $entity->getPersenPotongan();
            }
        }

        if ($totalNominalTransaksiSebelumnya == ($nominalBiaya - $nominalPotongan)
                && $totalNominalTransaksiSebelumnya > 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.paidoff'));
        } else {
            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);

            $editForm = $this
                    ->createForm(
                            new PembayaranPendaftaranCicilanType($this->container, $sid,
                                    $daftarBiayaPendaftaran[0]), $entity);

            return array(
                    'siswa' => $siswa, 'entity' => $entity,
                    'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                    'nominalBiaya' => $nominalBiaya, 'adaPotongan' => $adaPotongan,
                    'jenisPotongan' => $jenisPotongan, 'nominalPotongan' => $nominalPotongan,
                    'persenPotongan' => $persenPotongan, 'edit_form' => $editForm->createView(),
            );
        }
    }

    /**
     * Edits an existing PembayaranPendaftaran entity.
     *
     * @Route("/{id}/update", name="payment_registrationfee_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PembayaranPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }
        // total payment start here because of the unknown behavior during binding request
        $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran();

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($daftarBiayaPendaftaran[0]);

        $nominalBiaya = $biaya->getNominal();
        $adaPotongan = $entity->getAdaPotongan();
        $jenisPotongan = "";
        $nominalPotongan = 0;
        $persenPotongan = 0;
        if ($adaPotongan) {
            $jenisPotongan = $entity->getJenisPotongan();
            if ($jenisPotongan == 'nominal') {
                $nominalPotongan = $entity->getNominalPotongan();
            } else if ($jenisPotongan == 'persentase') {
                $nominalPotongan = $nominalBiaya * ($entity->getPersenPotongan() / 100);
                $persenPotongan = $entity->getPersenPotongan();
            }
        }

        if ($totalNominalTransaksiSebelumnya == ($nominalBiaya - $nominalPotongan)
                && $totalNominalTransaksiSebelumnya > 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.paidoff'));
        }

        $editForm = $this
                ->createForm(
                        new PembayaranPendaftaranCicilanType($this->container, $sid,
                                $daftarBiayaPendaftaran[0]), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            $entity
                    ->setDaftarBiayaPendaftaran(
                            array(
                                $entity->getDaftarBiayaPendaftaran()
                            ));

            $currentPaymentAmount = 0;
            foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                $transaksi->setDibuatOleh($this->getUser());
                $currentPaymentAmount += $transaksi->getNominalPembayaran();
            }

            $totalPayment = $totalPayment + $currentPaymentAmount;
            $payableAmount = $this
                    ->getPayableRegistrationFees($siswa->getTahun()->getId(), $siswa->getGelombang()->getId());
            $totalDiscount = $siswa->getTotalPotonganPembayaranPendaftaran();
            $payableAmountDiscounted = $payableAmount - $totalDiscount;

            if ($totalPayment == $payableAmountDiscounted) {
                $siswa->setLunasBiayaPendaftaran(true);
            }

            // print("\$totalPayment: $totalPayment<br />");
            // print("\$payableAmount: $payableAmount<br />");
            // print("\$totalDiscount: $totalDiscount<br />");
            // print("\$payableAmountDiscounted: $payableAmountDiscounted<br />");
            // exit;

            $em->persist($entity);
            $em->persist($siswa);

            $em->flush();

            $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                    ->findBy(
                            array(
                                'sekolah' => $sekolah, 'jenisLayanan' => 'c-pendaftaran-bayar',
                            ));

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSmsPendaftaran = $em->getRepository('FastSisdikBundle:LayananSmsPendaftaran')
                                ->findBy(
                                        array(
                                            'sekolah' => $sekolah, 'jenisLayanan' => 'c-pendaftaran-bayar'
                                        ));
                        foreach ($layananSmsPendaftaran as $layanan) {
                            if ($layanan instanceof LayananSmsPendaftaran) {
                                $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                $namaOrtuWali = "";
                                $ponselOrtuWali = "";
                                foreach ($siswa->getOrangtuaWali() as $orangtuaWali) {
                                    if ($orangtuaWali instanceof OrangtuaWali) {
                                        if ($orangtuaWali->isAktif()) {
                                            $namaOrtuWali = $orangtuaWali->getNama();
                                            $ponselOrtuWali = $orangtuaWali->getPonsel();
                                            break;
                                        }
                                    }
                                }

                                $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(),
                                        $tekstemplate);

                                $nomorTransaksi = "";
                                $em->refresh($entity);
                                foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                                    if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                                        $em->refresh($transaksi);
                                        $nomorTransaksi = $transaksi->getNomorTransaksi();
                                    }
                                }
                                $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi,
                                        $tekstemplate);

                                $daftarBiayaPendaftaranDibayar = array();
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biayaPendaftaran) {
                                    $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                                            ->find($biayaPendaftaran);
                                    $daftarBiayaPendaftaranDibayar[] = $biaya->getJenisbiaya()->getNama();
                                }
                                $tekstemplate = str_replace("%daftar-biaya%",
                                        (implode(", ", $daftarBiayaPendaftaranDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'),
                                        \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace("%besar-pembayaran%",
                                        $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                        $tekstemplate);

                                if ($ponselOrtuWali != "") {
                                    $messenger = $this->get('fast_sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {

                                            $messenger->setPhoneNumber($ponsel);
                                            $messenger->setMessage($tekstemplate);

                                            $logid = $messenger->setLogEntry();
                                            // $logid = 0; // for debugging

                                            $dlrurl = "http://" . $this->getRequest()->getHost()
                                                    . $this
                                                            ->generateUrl(
                                                                    "localapi_logsmskeluar_dlr_update",
                                                                    array(
                                                                            'logid' => $logid,
                                                                            'status' => "%d", 'time' => "%T"
                                                                    ));
                                            $messenger->setDeliveryReportURL(urldecode($dlrurl));

                                            $messenger->sendMessage();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($totalPayment == $payableAmountDiscounted) {
                $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em
                                    ->getRepository('FastSisdikBundle:LayananSmsPendaftaran')
                                    ->findBy(
                                            array(
                                                    'sekolah' => $sekolah,
                                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas'
                                            ));
                            foreach ($layananSmsPendaftaran as $layanan) {
                                if ($layanan instanceof LayananSmsPendaftaran) {
                                    $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                    $namaOrtuWali = "";
                                    $ponselOrtuWali = "";
                                    foreach ($siswa->getOrangtuaWali() as $orangtuaWali) {
                                        if ($orangtuaWali instanceof OrangtuaWali) {
                                            if ($orangtuaWali->isAktif()) {
                                                $namaOrtuWali = $orangtuaWali->getNama();
                                                $ponselOrtuWali = $orangtuaWali->getPonsel();
                                                break;
                                            }
                                        }
                                    }

                                    $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali,
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(),
                                            $tekstemplate);

                                    $formatter = new \NumberFormatter(
                                            $this->container->getParameter('locale'),
                                            \NumberFormatter::CURRENCY);
                                    $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                    $tekstemplate = str_replace("%total-pembayaran%",
                                            $symbol . ". " . number_format($totalPayment, 0, ',', '.'),
                                            $tekstemplate);

                                    if ($ponselOrtuWali != "") {
                                        $messenger = $this->get('fast_sisdik.messenger');
                                        if ($messenger instanceof Messenger) {
                                            $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                            foreach ($nomorponsel as $ponsel) {

                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);

                                                $logid = $messenger->setLogEntry();
                                                // $logid = 0; // for debugging

                                                $dlrurl = "http://" . $this->getRequest()->getHost()
                                                        . $this
                                                                ->generateUrl(
                                                                        "localapi_logsmskeluar_dlr_update",
                                                                        array(
                                                                                'logid' => $logid,
                                                                                'status' => "%d",
                                                                                'time' => "%T"
                                                                        ));
                                                $messenger->setDeliveryReportURL(urldecode($dlrurl));

                                                $messenger->sendMessage();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')->trans('flash.payment.registration.mortgage.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_registrationfee_show',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        $this->get('session')->getFlashBag()
                ->add('error',
                        $this->get('translator')->trans('flash.payment.registration.mortgage.fail.insert'));

        return array(
                'siswa' => $siswa, 'entity' => $entity,
                'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                'nominalBiaya' => $nominalBiaya, 'adaPotongan' => $adaPotongan,
                'jenisPotongan' => $jenisPotongan, 'nominalPotongan' => $nominalPotongan,
                'persenPotongan' => $persenPotongan, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Prints registration fee payment receipt
     *
     * @Route("/{pid}/printreceipt/{id}", name="payment_registrationfee_printreceipt")
     */
    public function printReceiptAction(Request $request, $sid, $pid, $id) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $pembayaran = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($pid);
        if (!(is_object($pembayaran) && $pembayaran instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $transaksi = $em->getRepository('FastSisdikBundle:TransaksiPembayaranPendaftaran')->find($id);
        if (!$transaksi && !($transaksi instanceof TransaksiPembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity TransaksiPembayaranPendaftaran tak ditemukan.');
        }

        $transaksiPembayaran = $em->getRepository('FastSisdikBundle:TransaksiPembayaranPendaftaran')
                ->findBy(
                        array(
                            'pembayaranPendaftaran' => $pid
                        ),
                        array(
                            'waktuSimpan' => 'ASC'
                        ));
        $counterTransaksi = 0;
        $nomorCicilan = 0;
        $nomorTransaksi = array();
        foreach ($transaksiPembayaran as $t) {
            if ($t instanceof TransaksiPembayaranPendaftaran) {
                $counterTransaksi++;
                $nomorTransaksi[$t->getNomorTransaksi()] = $t->getNomorTransaksi();
                if ($t->getId() == $id) {
                    $nomorCicilan = $counterTransaksi;
                    break;
                }
            }
        }
        $nomorCicilan = count($transaksiPembayaran) <= 1 ? 0 : $nomorCicilan;
        $adaCicilan = count($transaksiPembayaran) > 1 ? true : false;
        $totalPembayaranHinggaTransaksiTerpilih = $pembayaran
                ->getTotalNominalTransaksiPembayaranPendaftaranHinggaTransaksiTerpilih($nomorTransaksi);

        $tahun = $transaksi->getWaktuSimpan()->format('Y');
        $bulan = $transaksi->getWaktuSimpan()->format('m');
        $daftarBiayaPendaftaran = $pembayaran->getDaftarBiayaPendaftaran();

        $translator = $this->get('translator');
        $formatter = new \NumberFormatter($this->container->getParameter('locale'),
                \NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        $output = 'pdf';
        $pilihanCetak = $em->getRepository('FastSisdikBundle:PilihanCetakKwitansi')
                ->findBy(array(
                    'sekolah' => $sekolah->getId()
                ));
        foreach ($pilihanCetak as $pilihan) {
            $output = $pilihan->getOutput();
        }

        $fs = new Filesystem();
        $schoolReceiptDir = $this->get('kernel')->getRootDir() . self::RECEIPTS_DIR . $sekolah->getId();
        if (!$fs->exists($schoolReceiptDir)) {
            $fs->mkdir($schoolReceiptDir);
        }
        if (!$fs->exists($schoolReceiptDir . '/' . $tahun)) {
            $fs->mkdir($schoolReceiptDir . '/' . $tahun);
        }
        if (!$fs->exists($schoolReceiptDir . '/' . $tahun . '/' . $bulan)) {
            $fs->mkdir($schoolReceiptDir . '/' . $tahun . '/' . $bulan);
        }

        if ($output == 'esc_p') {
            $filetarget = $transaksi->getNomorTransaksi() . ".sisdik.direct";
            $documenttarget = $schoolReceiptDir . '/' . $tahun . '/' . $bulan . '/' . $filetarget;

            $commands = new EscapeCommand();
            $commands->addLineSpacing_1_6();
            $commands->addPageLength33Lines();
            $commands->addMarginBottom5Lines();
            $commands->addMaster10CPI();
            $commands->addMasterCondensed();
            $commands->addModeDraft();

            // max 137 characters
            $maxwidth = 137;
            $labelwidth1 = 20;
            $labelwidth2 = 15;
            $marginBadan = 7;
            $maxwidth2 = $maxwidth - $marginBadan;
            $spasi = "";

            $commands->addContent($sekolah->getNama() . "\r\n");
            $commands->addContent($sekolah->getAlamat() . ", " . $sekolah->getKodepos() . "\r\n");

            $phonefaxline = $sekolah->getTelepon() != "" ? $translator
                            ->trans('telephone', array(), 'printing') . " " . $sekolah->getTelepon() : "";
            $phonefaxline .= $sekolah->getFax() != "" ? ($phonefaxline != "" ? ", "
                                    . $translator->trans('faximile', array(), 'printing') . " "
                                    . $sekolah->getFax()
                            : $translator->trans('faximile', array(), 'printing') . " " . $sekolah->getFax())
                    : "";

            $commands->addContent($phonefaxline . "\r\n");

            $commands->addContent(str_repeat("=", $maxwidth) . "\r\n");

            $commands->addContent("\r\n");

            $nomorkwitansi = $translator->trans('receiptnum', array(), 'printing');
            $spasi = str_repeat(" ", ($labelwidth2 - strlen($nomorkwitansi)));
            $barisNomorkwitansi = $nomorkwitansi . $spasi . ": " . $transaksi->getNomorTransaksi();

            $namasiswa = $translator->trans('applicantname', array(), 'printing');
            $spasi = str_repeat(" ", ($labelwidth1 - strlen($namasiswa)));
            $barisNamasiswa = $namasiswa . $spasi . ": " . $siswa->getNamaLengkap();

            $tanggal = $translator->trans('date', array(), 'printing');
            $spasi = str_repeat(" ", ($labelwidth2 - strlen($tanggal)));
            $dateFormatter = $this->get('bcc_extra_tools.date_formatter');
            $barisTanggal = $tanggal . $spasi . ": "
                    . $dateFormatter->format($transaksi->getWaktuSimpan(), 'long');

            $nomorpendaftaran = $translator->trans('applicationnum', array(), 'printing');
            $spasi = str_repeat(" ", ($labelwidth1 - strlen($nomorpendaftaran)));
            $barisNomorPendaftaran = $nomorpendaftaran . $spasi . ": " . $siswa->getNomorPendaftaran();

            $pengisiBaris1 = strlen($barisNomorkwitansi);
            $pengisiBaris2 = strlen($barisTanggal);
            $pengisiBarisTerbesar = $pengisiBaris1 > $pengisiBaris2 ? $pengisiBaris1 : $pengisiBaris2;

            $sisaBaris1 = $maxwidth2 - strlen($barisNamasiswa) - $pengisiBarisTerbesar;
            $sisaBaris2 = $maxwidth2 - strlen($barisNomorPendaftaran) - $pengisiBarisTerbesar;

            $commands
                    ->addContent(
                            str_repeat(" ", $marginBadan) . $barisNamasiswa . str_repeat(" ", $sisaBaris1)
                                    . $barisNomorkwitansi . "\r\n");
            $commands
                    ->addContent(
                            str_repeat(" ", $marginBadan) . $barisNomorPendaftaran
                                    . str_repeat(" ", $sisaBaris2) . $barisTanggal . "\r\n");

            $commands->addContent("\r\n");

            $twoPages = false;
            if ($adaCicilan) {
                /****** kwitansi format cicilan: formular ******/

                $commands->addContent("\r\n");

                $labelwidth3 = 25;
                $symbolwidth = count($symbol);
                $pricewidth = 15;
                $lebarketerangan = 93;

                $namaItemPembayaran = "";
                $nominalHargaItemPembayaran = 0;
                foreach ($daftarBiayaPendaftaran as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    if ($biayaPendaftaran instanceof BiayaPendaftaran) {
                        $namaItemPembayaran = $biayaPendaftaran->getJenisbiaya()->getNama();
                        $nominalHargaItemPembayaran = $biayaPendaftaran->getNominal();
                    }
                }

                $labelItemPembayaran = $translator->trans('paymentitem', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelItemPembayaran)));
                $barisItemPembayaran = $labelItemPembayaran . $spasi . ": " . $namaItemPembayaran;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisItemPembayaran . "\r\n");

                $labelHargaItemPembayaran = $translator->trans('itemprice', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelHargaItemPembayaran)));
                $valueHargaItemPembayaran = number_format($nominalHargaItemPembayaran, 0, ',', '.');
                $spasi2 = str_repeat(" ", $pricewidth - (strlen($valueHargaItemPembayaran)));
                $barisHargaItemPembayaran = $labelHargaItemPembayaran . $spasi . ": " . $symbol . $spasi2
                        . $valueHargaItemPembayaran;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisHargaItemPembayaran . "\r\n");

                $nominalPotongan = 0;
                if ($pembayaran->getAdaPotongan()) {
                    $labelPotongan = $translator->trans('discount', array(), 'printing');
                    $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelPotongan)));
                    $persenPotongan = "";
                    if ($pembayaran->getJenisPotongan() == 'nominal') {
                        $nominalPotongan = $pembayaran->getNominalPotongan();
                        $valuePotongan = number_format($pembayaran->getNominalPotongan(), 0, ',', '.');
                    } else {
                        $nominalPotongan = $pembayaran->getPersenPotonganDinominalkan();
                        $valuePotongan = number_format($pembayaran->getPersenPotonganDinominalkan(), 0, ',',
                                '.');
                        $persenPotongan = " (" . $pembayaran->getPersenPotongan() . "%)";
                    }
                    $spasi2 = str_repeat(" ", $pricewidth - (strlen($valuePotongan)));
                    $barisPotongan = $labelPotongan . $spasi . ": " . $symbol . $spasi2 . $valuePotongan
                            . $persenPotongan;
                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisPotongan . "\r\n");

                    $labelTotalHarga = $translator->trans('totalprice', array(), 'printing');
                    $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelTotalHarga)));
                    $valueTotalHarga = number_format($nominalHargaItemPembayaran - $nominalPotongan, 0, ',',
                            '.');
                    $spasi2 = str_repeat(" ", $pricewidth - (strlen($valueTotalHarga)));
                    $barisTotalHarga = $labelTotalHarga . $spasi . ": " . $symbol . $spasi2
                            . $valueTotalHarga;
                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisTotalHarga . "\r\n");
                }

                $commands->addContent("\r\n");

                $labelPembayaranKe = $translator->trans('paymentnum', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelPembayaranKe)));
                $barisPembayaranKe = $labelPembayaranKe . $spasi . ": " . $nomorCicilan;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisPembayaranKe . "\r\n");

                $labelNominalPembayaran = $translator->trans('paymentamount', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelNominalPembayaran)));
                $valueNominalPembayaran = number_format($transaksi->getNominalPembayaran(), 0, ',', '.');
                $spasi2 = str_repeat(" ", $pricewidth - (strlen($valueNominalPembayaran)));
                $barisNominalPembayaran = $labelNominalPembayaran . $spasi . ": " . $symbol . $spasi2
                        . $valueNominalPembayaran;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisNominalPembayaran . "\r\n");

                $labelKeteranganPembayaran = $translator->trans('description', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelKeteranganPembayaran)));
                $valueKeteranganPembayaran = $transaksi->getKeterangan();
                $valueKeteranganPembayaran = strlen($valueKeteranganPembayaran) > $lebarketerangan ? substr(
                                $valueKeteranganPembayaran, 0, ($lebarketerangan - 3)) . "..."
                        : $valueKeteranganPembayaran;
                $barisKeteranganPembayaran = $labelKeteranganPembayaran . $spasi . ": "
                        . $valueKeteranganPembayaran;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisKeteranganPembayaran . "\r\n");

                $commands->addContent("\r\n");
                $commands->addContent(str_repeat(" ", $marginBadan) . "* * *\r\n");

                $labelTotalSudahBayar = $translator->trans('totalpaidamount', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelTotalSudahBayar)));
                $valueTotalSudahBayar = number_format($totalPembayaranHinggaTransaksiTerpilih, 0, ',', '.');
                $spasi2 = str_repeat(" ", $pricewidth - (strlen($valueTotalSudahBayar)));
                $barisTotalSudahBayar = $labelTotalSudahBayar . $spasi . ": " . $symbol . $spasi2
                        . $valueTotalSudahBayar;
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisTotalSudahBayar . "\r\n");

                $labelSisaPembayaran = $translator->trans('unpaidamount', array(), 'printing');
                $spasi = str_repeat(" ", ($labelwidth3 - strlen($labelSisaPembayaran)));
                $nominalSisaPembayaran = $nominalHargaItemPembayaran - $nominalPotongan
                        - $totalPembayaranHinggaTransaksiTerpilih;
                if ($nominalSisaPembayaran == 0) {
                    $valueSisaPembayaran = "(" . $translator->trans('settled', array(), 'printing') . ")";
                    $barisSisaPembayaran = $labelSisaPembayaran . $spasi . ": " . $valueSisaPembayaran;
                } else {
                    $valueSisaPembayaran = number_format($nominalSisaPembayaran, 0, ',', '.');
                    $spasi2 = str_repeat(" ", $pricewidth - (strlen($valueSisaPembayaran)));
                    $barisSisaPembayaran = $labelSisaPembayaran . $spasi . ": " . $symbol . $spasi2
                            . $valueSisaPembayaran;
                }
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisSisaPembayaran . "\r\n");

                if (!$pembayaran->getAdaPotongan()) {
                    $commands->addContent("\r\n");
                }
                $commands->addContent("\r\n");
                $commands->addContent("\r\n");

                /****** selesai kwitansi format cicilan ******/
            } else {
                /****** kwitansi format non-cicilan: tabular ******/

                $lebarKolom1 = 5;
                $lebarKolom2 = 70;
                $lebarKolom3 = 23;
                $marginKiriKolom = 1;
                $marginKananKolom = 1;

                $barisGarisTabel = "+" . str_repeat("-", $lebarKolom1) . "+" . str_repeat("-", $lebarKolom2)
                        . "+" . str_repeat("-", $lebarKolom3) . "+";

                $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                $kolomNomor = $translator->trans('num', array(), 'printing');
                $spasiKolomNomor = $lebarKolom1 - (strlen($kolomNomor) + $marginKiriKolom);
                $barisNamaKolom = "|" . str_repeat(" ", $marginKiriKolom) . $kolomNomor
                        . str_repeat(" ", $spasiKolomNomor);

                $kolomItem = $translator->trans('paymentitem', array(), 'printing');
                $spasiKolomItem = $lebarKolom2 - (strlen($kolomItem) + $marginKiriKolom);
                $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomItem
                        . str_repeat(" ", $spasiKolomItem);

                $kolomHarga = $translator->trans('price', array(), 'printing') . " ($symbol)";
                $spasiKolomHarga = $lebarKolom3 - (strlen($kolomHarga) + $marginKiriKolom);
                $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomHarga
                        . str_repeat(" ", $spasiKolomHarga) . "|";

                $commands->addContent(str_repeat(" ", $marginBadan) . $barisNamaKolom . "\r\n");
                $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                // uncomment following line for double page test
                // $daftarBiayaPendaftaran = array_merge($daftarBiayaPendaftaran);

                $maxitemPageone = 9;
                $itemThreshold = 15;
                if ($pembayaran->getAdaPotongan()) {
                    // jumlah item pembayaran maximum dalam 1 halaman: 7
                    // jumlah item pembayaran > 7, buat dua halaman
                    $maxitemPageone = 7;
                    if (count($daftarBiayaPendaftaran) > $maxitemPageone) {
                        $twoPages = true;
                    }
                    // jumlah item pembayaran 8 - 14: 6 item di halaman pertama, 8 - 14 di halaman 2
                    // jumlah item pembayaran >= 15: 13 item di halaman pertama, 14+ item di halaman 2
                } else {
                    if (count($daftarBiayaPendaftaran) > $maxitemPageone) {
                        $twoPages = true;
                    }
                }

                $twoPageFormat = 0;
                if ($twoPages === true && count($daftarBiayaPendaftaran) < $itemThreshold) {
                    $twoPageFormat = 1;
                } else if ($twoPages === true && count($daftarBiayaPendaftaran) >= $itemThreshold) {
                    $maxitemPageone = 14;
                    $twoPageFormat = 2;
                }

                $num = 1;
                $totalNominalTransaksi = 0;

                foreach ($daftarBiayaPendaftaran as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    if ($biayaPendaftaran instanceof BiayaPendaftaran) {
                        $totalNominalTransaksi += $biayaPendaftaran->getNominal();

                        if ($twoPageFormat == 1 && $num == $maxitemPageone) {
                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $barisKeteranganBerlanjut = $translator
                                    ->trans('continue.to.pagetwo', array(), 'printing');
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $barisKeteranganBerlanjut
                                                    . "\r\n");

                            if ($pembayaran->getAdaPotongan()) {
                                $commands->addContent("\r\n");
                                $commands->addContent("\r\n");
                            }
                            $commands->addContent("\r\n");
                            $commands->addContent("\r\n");
                            $commands->addContent("\r\n");
                            $commands->addContent("\r\n");
                            $commands->addContent("\r\n");
                            $commands->addContent("\r\n");

                            $labelwidthHal2 = 18;

                            $barisHalaman1 = "(" . $translator->trans('page', array(), 'printing') . " 1/2)";
                            $spasiBarisHalaman1 = str_repeat(" ", ($maxwidth2 - strlen($barisHalaman1)));
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $spasiBarisHalaman1
                                                    . $barisHalaman1 . "\r\n");

                            $nomorkwitansiHal2 = $translator->trans('receiptnum', array(), 'printing');
                            $spasi = str_repeat(" ", ($labelwidthHal2 - strlen($nomorkwitansiHal2)));
                            $barisNomorkwitansiHal2 = $nomorkwitansiHal2 . $spasi . ": "
                                    . $transaksi->getNomorTransaksi();

                            $nomorpendaftaranHal2 = $translator->trans('applicationnum', array(), 'printing');
                            $spasi = str_repeat(" ", ($labelwidthHal2 - strlen($nomorpendaftaranHal2)));
                            $barisNomorpendaftaranHal2 = $nomorpendaftaranHal2 . $spasi . ": "
                                    . $transaksi->getPembayaranPendaftaran()->getSiswa()
                                            ->getNomorPendaftaran();

                            $pengisiBaris1 = strlen($barisNomorkwitansiHal2);
                            $pengisiBaris2 = strlen($barisNomorpendaftaranHal2);
                            $pengisiBarisTerbesar = $pengisiBaris1 > $pengisiBaris2 ? $pengisiBaris1
                                    : $pengisiBaris2;

                            $sisaBaris = $maxwidth2 - $pengisiBarisTerbesar;

                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . str_repeat(" ", $sisaBaris)
                                                    . $barisNomorkwitansiHal2 . "\r\n");
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . str_repeat(" ", $sisaBaris)
                                                    . $barisNomorpendaftaranHal2 . "\r\n");

                            $barisKeteranganLanjutan = $translator
                                    ->trans('continued.from.pageone', array(), 'printing');
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $barisKeteranganLanjutan . "\r\n");

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $kolomNomor = $translator->trans('num', array(), 'printing');
                            $spasiKolomNomor = $lebarKolom1 - (strlen($kolomNomor) + $marginKiriKolom);
                            $barisNamaKolom = "|" . str_repeat(" ", $marginKiriKolom) . $kolomNomor
                                    . str_repeat(" ", $spasiKolomNomor);

                            $kolomItem = $translator->trans('paymentitem', array(), 'printing');
                            $spasiKolomItem = $lebarKolom2 - (strlen($kolomItem) + $marginKiriKolom);
                            $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomItem
                                    . str_repeat(" ", $spasiKolomItem);

                            $kolomHarga = $translator->trans('price', array(), 'printing') . " ($symbol)";
                            $spasiKolomHarga = $lebarKolom3 - (strlen($kolomHarga) + $marginKiriKolom);
                            $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomHarga
                                    . str_repeat(" ", $spasiKolomHarga) . "|";

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisNamaKolom . "\r\n");
                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $kolomNomorPembayaran = strval($num);
                            $spasiKolomNomorPembayaran = $lebarKolom1
                                    - (strlen($kolomNomorPembayaran) + $marginKananKolom);
                            $barisPembayaran = "|" . str_repeat(" ", $spasiKolomNomorPembayaran)
                                    . $kolomNomorPembayaran . str_repeat(" ", $marginKananKolom);

                            $kolomItemPembayaran = $biayaPendaftaran->getJenisbiaya()->getNama();
                            $spasiKolomItemPembayaran = $lebarKolom2
                                    - (strlen($kolomItemPembayaran) + $marginKiriKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $marginKiriKolom)
                                    . $kolomItemPembayaran . str_repeat(" ", $spasiKolomItemPembayaran);

                            $kolomHargaPembayaran = number_format($biayaPendaftaran->getNominal(), 0, ',',
                                    '.');
                            $spasiKolomHargaPembayaran = $lebarKolom3
                                    - (strlen($kolomHargaPembayaran) + $marginKananKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $spasiKolomHargaPembayaran)
                                    . $kolomHargaPembayaran . str_repeat(" ", $marginKananKolom) . "|";

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisPembayaran . "\r\n");

                        } else if ($twoPageFormat == 2 && $num == $maxitemPageone) {

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $barisKeteranganBerlanjut = $translator
                                    ->trans('continue.to.pagetwo', array(), 'printing');
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $barisKeteranganBerlanjut
                                                    . "\r\n");

                            $commands->addContent("\r\n");

                            $labelwidthHal2 = 18;

                            $barisHalaman1 = "(" . $translator->trans('page', array(), 'printing') . " 1/2)";
                            $spasiBarisHalaman1 = str_repeat(" ", ($maxwidth2 - strlen($barisHalaman1)));
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $spasiBarisHalaman1
                                                    . $barisHalaman1 . "\r\n");

                            $nomorkwitansiHal2 = $translator->trans('receiptnum', array(), 'printing');
                            $spasi = str_repeat(" ", ($labelwidthHal2 - strlen($nomorkwitansiHal2)));
                            $barisNomorkwitansiHal2 = $nomorkwitansiHal2 . $spasi . ": "
                                    . $transaksi->getNomorTransaksi();

                            $nomorpendaftaranHal2 = $translator->trans('applicationnum', array(), 'printing');
                            $spasi = str_repeat(" ", ($labelwidthHal2 - strlen($nomorpendaftaranHal2)));
                            $barisNomorpendaftaranHal2 = $nomorpendaftaranHal2 . $spasi . ": "
                                    . $transaksi->getPembayaranPendaftaran()->getSiswa()
                                            ->getNomorPendaftaran();

                            $pengisiBaris1 = strlen($barisNomorkwitansiHal2);
                            $pengisiBaris2 = strlen($barisNomorpendaftaranHal2);
                            $pengisiBarisTerbesar = $pengisiBaris1 > $pengisiBaris2 ? $pengisiBaris1
                                    : $pengisiBaris2;

                            $sisaBaris = $maxwidth2 - $pengisiBarisTerbesar;

                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . str_repeat(" ", $sisaBaris)
                                                    . $barisNomorkwitansiHal2 . "\r\n");
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . str_repeat(" ", $sisaBaris)
                                                    . $barisNomorpendaftaranHal2 . "\r\n");

                            $barisKeteranganLanjutan = $translator
                                    ->trans('continued.from.pageone', array(), 'printing');
                            $commands
                                    ->addContent(
                                            str_repeat(" ", $marginBadan) . $barisKeteranganLanjutan . "\r\n");

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $kolomNomor = $translator->trans('num', array(), 'printing');
                            $spasiKolomNomor = $lebarKolom1 - (strlen($kolomNomor) + $marginKiriKolom);
                            $barisNamaKolom = "|" . str_repeat(" ", $marginKiriKolom) . $kolomNomor
                                    . str_repeat(" ", $spasiKolomNomor);

                            $kolomItem = $translator->trans('paymentitem', array(), 'printing');
                            $spasiKolomItem = $lebarKolom2 - (strlen($kolomItem) + $marginKiriKolom);
                            $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomItem
                                    . str_repeat(" ", $spasiKolomItem);

                            $kolomHarga = $translator->trans('price', array(), 'printing') . " ($symbol)";
                            $spasiKolomHarga = $lebarKolom3 - (strlen($kolomHarga) + $marginKiriKolom);
                            $barisNamaKolom .= "|" . str_repeat(" ", $marginKiriKolom) . $kolomHarga
                                    . str_repeat(" ", $spasiKolomHarga) . "|";

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisNamaKolom . "\r\n");
                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                            $kolomNomorPembayaran = strval($num);
                            $spasiKolomNomorPembayaran = $lebarKolom1
                                    - (strlen($kolomNomorPembayaran) + $marginKananKolom);
                            $barisPembayaran = "|" . str_repeat(" ", $spasiKolomNomorPembayaran)
                                    . $kolomNomorPembayaran . str_repeat(" ", $marginKananKolom);

                            $kolomItemPembayaran = $biayaPendaftaran->getJenisbiaya()->getNama();
                            $spasiKolomItemPembayaran = $lebarKolom2
                                    - (strlen($kolomItemPembayaran) + $marginKiriKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $marginKiriKolom)
                                    . $kolomItemPembayaran . str_repeat(" ", $spasiKolomItemPembayaran);

                            $kolomHargaPembayaran = number_format($biayaPendaftaran->getNominal(), 0, ',',
                                    '.');
                            $spasiKolomHargaPembayaran = $lebarKolom3
                                    - (strlen($kolomHargaPembayaran) + $marginKananKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $spasiKolomHargaPembayaran)
                                    . $kolomHargaPembayaran . str_repeat(" ", $marginKananKolom) . "|";

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisPembayaran . "\r\n");

                        } else {
                            $kolomNomorPembayaran = strval($num);
                            $spasiKolomNomorPembayaran = $lebarKolom1
                                    - (strlen($kolomNomorPembayaran) + $marginKananKolom);
                            $barisPembayaran = "|" . str_repeat(" ", $spasiKolomNomorPembayaran)
                                    . $kolomNomorPembayaran . str_repeat(" ", $marginKananKolom);

                            $kolomItemPembayaran = $biayaPendaftaran->getJenisbiaya()->getNama();
                            $spasiKolomItemPembayaran = $lebarKolom2
                                    - (strlen($kolomItemPembayaran) + $marginKiriKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $marginKiriKolom)
                                    . $kolomItemPembayaran . str_repeat(" ", $spasiKolomItemPembayaran);

                            $kolomHargaPembayaran = number_format($biayaPendaftaran->getNominal(), 0, ',',
                                    '.');
                            $spasiKolomHargaPembayaran = $lebarKolom3
                                    - (strlen($kolomHargaPembayaran) + $marginKananKolom);
                            $barisPembayaran .= "|" . str_repeat(" ", $spasiKolomHargaPembayaran)
                                    . $kolomHargaPembayaran . str_repeat(" ", $marginKananKolom) . "|";

                            $commands->addContent(str_repeat(" ", $marginBadan) . $barisPembayaran . "\r\n");
                        }
                    }

                    $num++;
                }

                $commands->addContent(str_repeat(" ", $marginBadan) . $barisGarisTabel . "\r\n");

                $lebarKolom4 = 55;
                $lebarKolom5 = 22;
                $lebarKolom6 = 23;

                $kolomKeterangan = $translator->trans('description', array(), 'printing');
                $kolomKeterangan .= " : " . $transaksi->getKeterangan();
                $kolomKeterangan = strlen($kolomKeterangan) > $lebarKolom4 ? substr($kolomKeterangan, 0,
                                ($lebarKolom4 - 3)) . "..." : $kolomKeterangan;
                $spasiKolomKeterangan = $lebarKolom4 - (strlen($kolomKeterangan));
                $barisKolomTotal = $kolomKeterangan . str_repeat(" ", $spasiKolomKeterangan);

                if ($pembayaran->getAdaPotongan()) {

                    $kolomSubtotal = $translator->trans('subtotal', array(), 'printing');
                    $spasiKolomSubtotal = $lebarKolom5 - (strlen($kolomSubtotal) + $marginKananKolom);
                    $barisKolomSubtotal = $barisKolomTotal . str_repeat(" ", $spasiKolomSubtotal)
                            . $kolomSubtotal . str_repeat(" ", $marginKananKolom) . ":";

                    $kolomSubtotalHarga = number_format($totalNominalTransaksi, 0, ',', '.');
                    $spasiKolomSubtotalHarga = $lebarKolom6
                            - (strlen($kolomSubtotalHarga) + $marginKananKolom);
                    $barisKolomSubtotal .= str_repeat(" ", $spasiKolomSubtotalHarga) . $kolomSubtotalHarga
                            . str_repeat(" ", $marginKananKolom) . " ";

                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisKolomSubtotal . "\r\n");

                    $nominalPotongan = 0;
                    $persenPotongan = "";
                    if ($pembayaran->getJenisPotongan() == 'nominal') {
                        $nominalPotongan = $pembayaran->getNominalPotongan();
                    } else {
                        $nominalPotongan = $pembayaran->getPersenPotonganDinominalkan();
                        $persenPotongan = " (" . $pembayaran->getPersenPotongan() . "%)";
                    }
                    $kolomPotongan = $translator->trans('discount', array(), 'printing');
                    $spasiKolomPotongan = $lebarKolom4 + $lebarKolom5
                            - (strlen($kolomPotongan) + $marginKananKolom);
                    $barisKolomPotongan = str_repeat(" ", $spasiKolomPotongan) . $kolomPotongan
                            . str_repeat(" ", $marginKananKolom) . ":";

                    $kolomPotonganHarga = number_format($nominalPotongan, 0, ',', '.');
                    $spasiKolomPotonganHarga = $lebarKolom6
                            - (strlen($kolomPotonganHarga) + $marginKananKolom);
                    $barisKolomPotongan .= str_repeat(" ", $spasiKolomPotonganHarga) . $kolomPotonganHarga
                            . str_repeat(" ", $marginKananKolom) . $persenPotongan;

                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisKolomPotongan . "\r\n");

                    $kolomTotal = $translator->trans('total', array(), 'printing');
                    $spasiKolomTotal = $lebarKolom4 + $lebarKolom5
                            - (strlen($kolomTotal) + $marginKananKolom);
                    $barisKolomTotal = str_repeat(" ", $spasiKolomTotal) . $kolomTotal
                            . str_repeat(" ", $marginKananKolom) . ":";

                    $kolomTotalHarga = number_format($totalNominalTransaksi - $nominalPotongan, 0, ',', '.');
                    $spasiKolomTotalHarga = $lebarKolom6 - (strlen($kolomTotalHarga) + $marginKananKolom);
                    $barisKolomTotal .= str_repeat(" ", $spasiKolomTotalHarga) . $kolomTotalHarga
                            . str_repeat(" ", $marginKananKolom) . " ";

                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisKolomTotal . "\r\n");

                    $commands->addContent("\r\n");

                    if (count($daftarBiayaPendaftaran) < $maxitemPageone) {
                        $maxJarakVertikal = $maxitemPageone;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - count($daftarBiayaPendaftaran));
                        $commands->addContent($jarakVertikal);
                    }
                    if ($twoPageFormat == 1) {
                        $maxJarakVertikal = 10;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - (count($daftarBiayaPendaftaran) - ($maxitemPageone + 1)));
                        $commands->addContent($jarakVertikal);
                    }
                    if ($twoPageFormat == 2) {
                        $maxJarakVertikal = 10;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - (count($daftarBiayaPendaftaran) - 15));
                        $commands->addContent($jarakVertikal);
                    }

                } else {

                    $kolomTotal = $translator->trans('total', array(), 'printing');
                    $spasiKolomTotal = $lebarKolom5 - (strlen($kolomTotal) + $marginKananKolom);
                    $barisKolomTotal .= str_repeat(" ", $spasiKolomTotal) . $kolomTotal
                            . str_repeat(" ", $marginKananKolom) . ":";

                    $kolomTotalHarga = number_format($totalNominalTransaksi, 0, ',', '.');
                    $spasiKolomTotalHarga = $lebarKolom6 - (strlen($kolomTotalHarga) + $marginKananKolom);
                    $barisKolomTotal .= str_repeat(" ", $spasiKolomTotalHarga) . $kolomTotalHarga
                            . str_repeat(" ", $marginKananKolom) . " ";

                    $commands->addContent(str_repeat(" ", $marginBadan) . $barisKolomTotal . "\r\n");

                    $commands->addContent("\r\n");

                    if (count($daftarBiayaPendaftaran) < $maxitemPageone) {
                        $maxJarakVertikal = 9;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - count($daftarBiayaPendaftaran));
                        $commands->addContent($jarakVertikal);
                    }
                    if ($twoPageFormat == 1) {
                        $maxJarakVertikal = 12;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - (count($daftarBiayaPendaftaran) - ($maxitemPageone + 1)));
                        $commands->addContent($jarakVertikal);
                    }
                    if ($twoPageFormat == 2) {
                        $maxJarakVertikal = 12;
                        $jarakVertikal = str_repeat("\r\n",
                                $maxJarakVertikal - (count($daftarBiayaPendaftaran) - 15));
                        $commands->addContent($jarakVertikal);
                    }

                }

                /****** selesai kwitansi format non-cicilan ******/
            }

            $marginKiriTtd = 20;
            $lebarKolom7 = 62;
            $lebarKolom8 = 59;

            $kolomPendaftar1 = $translator->trans('applicant.name', array(), 'printing');
            $spasiKolomPendaftar1 = $lebarKolom7 - (strlen($kolomPendaftar1) + $marginKiriTtd);
            $barisTandatangan1 = str_repeat(" ", $marginKiriTtd) . $kolomPendaftar1
                    . str_repeat(" ", $spasiKolomPendaftar1);

            $kolomPenerima1 = $translator->trans('cashier.or.treasurer', array(), 'printing');
            $spasiKolomPenerima1 = $lebarKolom8 - strlen($kolomPenerima1);
            $barisTandatangan1 .= $kolomPenerima1 . str_repeat(" ", $spasiKolomPenerima1);

            $commands->addContent($barisTandatangan1 . "\r\n");

            $commands->addContent("\r\n");
            $commands->addContent("\r\n");
            $commands->addContent("\r\n");

            $kolomPendaftar2 = $siswa->getNamaLengkap();
            $spasiKolomPendaftar2 = $lebarKolom7 - (strlen($kolomPendaftar2) + $marginKiriTtd);
            $barisTandatangan2 = str_repeat(" ", $marginKiriTtd) . $kolomPendaftar2
                    . str_repeat(" ", $spasiKolomPendaftar2);

            $kolomPenerima2 = $transaksi->getDibuatOleh()->getName();
            $spasiKolomPenerima2 = $lebarKolom8 - strlen($kolomPenerima2);
            $barisTandatangan2 .= $kolomPenerima2 . str_repeat(" ", $spasiKolomPenerima2);

            if ($twoPages === true) {
                $commands
                        ->addContent(
                                $barisTandatangan2 . "(" . $translator->trans('page', array(), 'printing')
                                        . " 2/2)");
            } else {
                $commands
                        ->addContent(
                                $barisTandatangan2 . "(" . $translator->trans('page', array(), 'printing')
                                        . " 1/1)");
            }

            $commands->addFormFeed();
            $commands->addResetCommand();

            $fp = fopen($documenttarget, "w");

            if (!$fp) {
                throw new IOException($translator->trans("exception.directprint.file"));
            } else {
                fwrite($fp, $commands->getCommands());
                fclose($fp);
            }

            $response = new Response(file_get_contents($documenttarget), 200);
            $response->headers->set('Content-Description', 'Dokumen kwitansi');
            $response->headers->set('Content-Type', 'application/vnd.sisdik.directprint');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', filesize($documenttarget));

        } else {

            $filetarget = $transaksi->getNomorTransaksi() . ".sisdik.pdf";
            $documenttarget = $schoolReceiptDir . '/' . $tahun . '/' . $bulan . '/' . $filetarget;

            $totalHarga = 0;
            foreach ($daftarBiayaPendaftaran as $biaya) {
                $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                if ($biayaPendaftaran instanceof BiayaPendaftaran) {
                    $namaItemPembayaranCicilan = $biayaPendaftaran->getJenisbiaya()->getNama();
                    $totalHarga += $biayaPendaftaran->getNominal();
                }
            }

            $facade = $this->get('ps_pdf.facade');
            $tmpResponse = new Response();

            $this
                    ->render('FastSisdikBundle:PembayaranPendaftaran:receipts.pdf.twig',
                            array(
                                    'sekolah' => $sekolah, 'siswa' => $siswa, 'pembayaran' => $pembayaran,
                                    'transaksi' => $transaksi, 'totalHarga' => $totalHarga,
                                    'adaCicilan' => $adaCicilan,
                                    'namaItemPembayaranCicilan' => $namaItemPembayaranCicilan,
                                    'nomorCicilan' => $nomorCicilan,
                                    'totalPembayaranHinggaTransaksiTerpilih' => $totalPembayaranHinggaTransaksiTerpilih,
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

            $response = new Response(file_get_contents($documenttarget), 200);
            $d = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filetarget);
            $response->headers->set('Content-Disposition', $d);
            $response->headers->set('Content-Description', 'Dokumen kwitansi');
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', filesize($documenttarget));
        }

        return $response;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.applicant.payment']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
