<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Util\Messenger;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Entity\LayananSmsPendaftaran;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Component\Form\FormError;
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

/**
 * PembayaranPendaftaran controller.
 *
 * @Route("/payment/registrationfee/{sid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PembayaranPendaftaranController extends Controller
{
    const RECEIPTS_DIR = "/receipts/";

    /**
     * Lists all PembayaranPendaftaran entities.
     *
     * @Route("/", name="payment_registrationfee")
     * @Method("GET")
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
                    } elseif ($jenisPotongan == 'persentase') {
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
            $entity->setJenisPotongan("nominal");
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
     * @Route("/", name="payment_registrationfee_create")
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
                    } elseif ($jenisPotongan == 'persentase') {
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
        $form->submit($request);

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

            if ($entity->getAdaPotongan() === false) {
                $entity->setJenisPotongan(null);
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
            } elseif ($jenisPotongan == 'persentase') {
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
            } elseif ($jenisPotongan == 'persentase') {
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
        // total payment start here because of the unknown behavior during submiting request
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
            } elseif ($jenisPotongan == 'persentase') {
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
        $editForm->submit($request);

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

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.applicant.payment']->setCurrent(true);
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
