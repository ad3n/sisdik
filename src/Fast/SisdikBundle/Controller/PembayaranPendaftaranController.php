<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\Gelombang;
use Fast\SisdikBundle\Entity\DaftarBiayaPendaftaran;
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
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this
                    ->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $itemBiaya = $this->getBiayaProperties($siswa);

        if (count($itemBiaya['semua']) == count($itemBiaya['tersimpan'])
                && count($itemBiaya['tersimpan']) > 0) {
            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'itemBiayaSemua' => $itemBiaya['semua'],
                    'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
                    'itemBiayaTersisa' => $itemBiaya['tersisa'],
            );
        } else {
            $entity = new PembayaranPendaftaran();
            $entity->setJenisPotongan("nominal");

            foreach ($itemBiaya['tersisa'] as $id) {
                $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

                $daftarBiaya = new DaftarBiayaPendaftaran();
                $daftarBiaya->setBiayaPendaftaran($biaya);
                $daftarBiaya->setNama($biaya->getJenisbiaya()->getNama());
                $daftarBiaya->setNominal($biaya->getNominal());

                $entity->getDaftarBiayaPendaftaran()->add($daftarBiaya);
            }

            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);
            $entity->setSiswa($siswa);

            $form = $this->createForm(new PembayaranPendaftaranType($this->container), $entity);

            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'itemBiayaSemua' => $itemBiaya['semua'],
                    'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
                    'itemBiayaTersisa' => $itemBiaya['tersisa'], 'form' => $form->createView(),
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
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this
                    ->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $itemBiaya = $this->getBiayaProperties($siswa);

        $entity = new PembayaranPendaftaran();
        $form = $this->createForm(new PembayaranPendaftaranType($this->container), $entity);
        $form->submit($request);

        // periksa apakah item pembayaran yang akan dimasukkan telah ada di database
        // ini untuk mencegah input ganda
        $formDaftarBiayaPendaftaran = $form->get('daftarBiayaPendaftaran')->getData();
        foreach ($formDaftarBiayaPendaftaran as $item) {
            if ($item instanceof DaftarBiayaPendaftaran) {
                if (in_array($item->getBiayaPendaftaran()->getId(), $itemBiaya['tersimpan'])) {
                    $this->get('session')->getFlashBag()
                            ->add('error',
                                    $this->get('translator')->trans('alert.registrationfee.is.inserted'));
                    return $this
                            ->redirect(
                                    $this
                                            ->generateUrl('payment_registrationfee',
                                                    array(
                                                        'sid' => $sid,
                                                    )));
                }
            }
        }

        if ($form->isValid()) {

            $now = new \DateTime();
            $qbmaxnum = $em->createQueryBuilder()->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                    ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                    ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                    ->setParameter('tahunsimpan', $now->format('Y'))
                    ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                    ->setParameter('bulansimpan', $now->format('m'))
                    ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

            $currentPaymentAmount = 0;
            foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                    $currentPaymentAmount = $transaksi->getNominalPembayaran();
                    $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                    $transaksi
                            ->setNomorTransaksi(
                                    TransaksiPembayaranPendaftaran::tandakwitansi . $now->format('Y')
                                            . $now->format('m') . ($nomormax + 1));
                }
            }
            $entity->setNominalTotalTransaksi($entity->getNominalTotalTransaksi() + $currentPaymentAmount);

            $nominalBiaya = 0;
            $itemBiayaTerproses = array();
            foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                if ($biaya instanceof DaftarBiayaPendaftaran) {
                    if (!$biaya->isTerpilih()) {
                        $entity->getDaftarBiayaPendaftaran()->removeElement($biaya);
                        continue;
                    }
                    $nominalBiaya += $biaya->getNominal();
                    $itemBiayaTerproses[] = $biaya->getBiayaPendaftaran()->getId();

                    $biayaPendaftaranTmp = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                            ->find($biaya->getBiayaPendaftaran()->getId());
                    $biayaPendaftaranTmp->setTerpakai(true);

                    $em->persist($biayaPendaftaranTmp);
                }
            }
            $entity->setNominalTotalBiaya($nominalBiaya);

            if ($entity->getAdaPotongan() === false) {
                $entity->setJenisPotongan(null);
                $entity->setNominalPotongan(0);
                $entity->setPersenPotongan(0);
                $entity->setPersenPotonganDinominalkan(0);
            }

            if ($entity->getAdaPotongan() && $entity->getPersenPotongan() != 0) {
                $persenPotonganDinominalkan = $nominalBiaya * ($entity->getPersenPotongan() / 100);
                $entity->setPersenPotonganDinominalkan($persenPotonganDinominalkan);
                $entity->setNominalPotongan(0);
            } else {
                $entity->setPersenPotongan(0);
                $entity->setPersenPotonganDinominalkan(0);
            }
            $currentDiscount = $entity->getNominalPotongan() + $entity->getPersenPotonganDinominalkan();

            $payableAmountDue = $siswa->getTotalNominalBiayaPendaftaran();
            $payableAmountRemain = $this
                    ->getPayableFeesRemain($siswa->getTahun()->getId(), $siswa->getGelombang()->getId(),
                            array_diff($itemBiaya['tersisa'], $itemBiayaTerproses));

            $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran() + $currentPaymentAmount;
            $totalDiscount = $siswa->getTotalPotonganPembayaranPendaftaran() + $currentDiscount;

            if (($payableAmountRemain + $payableAmountDue) == ($totalPayment + $totalDiscount)) {
                $siswa->setLunasBiayaPendaftaran(true);
            }
            $siswa->setSisaBiayaPendaftaran($payableAmountRemain);

            // print("\$totalPayment: $totalPayment<br />");
            // print("\$totalDiscount: $totalDiscount<br />");
            // print("\$payableAmountDue: $payableAmountDue<br />");
            // print("\$payableAmountRemain: $payableAmountRemain<br />");
            // exit;

            $em->persist($entity);
            $em->persist($siswa);

            $em->flush();

            if (count($itemBiaya['tersimpan']) == 0) {
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
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->get('fast_sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);
                                                $messenger->sendMessage($sekolah);
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
                                $daftarBiayaDibayar = array();
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                                    if ($counter > 3) {
                                        $daftarBiayaDibayar[] = $this->get('translator')->trans('dll');
                                        break;
                                    }
                                    $daftarBiayaDibayar[] = $biaya->getNama();
                                    $counter++;
                                }
                                $tekstemplate = str_replace("%daftar-biaya%",
                                        (implode(", ", $daftarBiayaDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'),
                                        \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace("%besar-pembayaran%",
                                        $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                        $tekstemplate);

                                if ($ponselOrtuWali != "") {
                                    $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                    foreach ($nomorponsel as $ponsel) {
                                        $messenger = $this->get('fast_sisdik.messenger');
                                        if ($messenger instanceof Messenger) {
                                            $messenger->setPhoneNumber($ponsel);
                                            $messenger->setMessage($tekstemplate);
                                            $messenger->sendMessage($sekolah);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($siswa->getLunasBiayaPendaftaran()) {
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
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->get('fast_sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);
                                                $messenger->sendMessage($sekolah);
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
                'entities' => $entities, 'siswa' => $siswa, 'itemBiayaSemua' => $itemBiaya['semua'],
                'itemBiayaTersimpan' => $itemBiaya['tersimpan'], 'itemBiayaTersisa' => $itemBiaya['tersisa'],
                'form' => $form->createView(),
        );
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
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this
                    ->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        $nominalBiaya = 0;
        foreach ($entity->getDaftarBiayaPendaftaran() as $daftar) {
            $nominalBiaya += $daftar->getNominal();
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
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this
                    ->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

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

        $biaya = $daftarBiayaPendaftaran->current();

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

            $editForm = $this->createForm(new PembayaranPendaftaranCicilanType($this->container), $entity);

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
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this
                    ->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }
        // total payment start here because of the unknown behavior during submitting request
        $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran();

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $itemBiaya = $this->getBiayaProperties($siswa);

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $nominalBiaya = $daftarBiayaPendaftaran[0]->getNominal();
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

        $editForm = $this->createForm(new PembayaranPendaftaranCicilanType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            $now = new \DateTime();
            $qbmaxnum = $em->createQueryBuilder()->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                    ->from('FastSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                    ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                    ->setParameter('tahunsimpan', $now->format('Y'))
                    ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                    ->setParameter('bulansimpan', $now->format('m'))
                    ->andWhere('transaksi.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

            $currentPaymentAmount = 0;
            $transaksi = $entity->getTransaksiPembayaranPendaftaran()->last();
            if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                $currentPaymentAmount = $transaksi->getNominalPembayaran();
                $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                $transaksi
                        ->setNomorTransaksi(
                                TransaksiPembayaranPendaftaran::tandakwitansi . $now->format('Y')
                                        . $now->format('m') . ($nomormax + 1));
            }
            $entity->setNominalTotalTransaksi($entity->getNominalTotalTransaksi() + $currentPaymentAmount);

            $payableAmountDue = $siswa->getTotalNominalBiayaPendaftaran();
            $payableAmountRemain = $this
                    ->getPayableFeesRemain($siswa->getTahun()->getId(), $siswa->getGelombang()->getId(),
                            $itemBiaya['tersisa']);

            $totalPayment = $totalPayment + $currentPaymentAmount;
            $totalDiscount = $siswa->getTotalPotonganPembayaranPendaftaran();

            if (($payableAmountRemain + $payableAmountDue) == ($totalPayment + $totalDiscount)) {
                $siswa->setLunasBiayaPendaftaran(true);
            }

            // print("\$totalPayment: $totalPayment<br />");
            // print("\$totalDiscount: $totalDiscount<br />");
            // print("\$payableAmountDue: $payableAmountDue<br />");
            // print("\$payableAmountRemain: $payableAmountRemain<br />");
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

                                $daftarBiayaDibayar = array();
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                                    $daftarBiayaDibayar[] = $biaya->getNama();
                                }
                                $tekstemplate = str_replace("%daftar-biaya%",
                                        (implode(", ", $daftarBiayaDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'),
                                        \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace("%besar-pembayaran%",
                                        $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                        $tekstemplate);

                                if ($ponselOrtuWali != "") {
                                    $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                    foreach ($nomorponsel as $ponsel) {
                                        $messenger = $this->get('fast_sisdik.messenger');
                                        if ($messenger instanceof Messenger) {
                                            $messenger->setPhoneNumber($ponsel);
                                            $messenger->setMessage($tekstemplate);
                                            $messenger->sendMessage($sekolah);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($siswa->getLunasBiayaPendaftaran()) {
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
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->get('fast_sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);
                                                $messenger->sendMessage($sekolah);
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

        $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
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
                ->from('FastSisdikBundle:DaftarBiayaPendaftaran', 'daftar')
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
     * Get payable registration fee amount
     *
     */
    private function getPayableFeesRemain($tahun, $gelombang, $remainfee) {
        $em = $this->getDoctrine()->getManager();

        if (is_array($remainfee) && count($remainfee) > 0) {
            $querybuilder = $em->createQueryBuilder()->select('biaya')
                    ->from('FastSisdikBundle:BiayaPendaftaran', 'biaya')->where('biaya.tahun = :tahun')
                    ->andWhere('biaya.gelombang = :gelombang')->setParameter("tahun", $tahun)
                    ->setParameter("gelombang", $gelombang)->andWhere('biaya.id IN (?1)')
                    ->setParameter(1, $remainfee);
            $entities = $querybuilder->getQuery()->getResult();

            $feeamount = 0;
            foreach ($entities as $entity) {
                if ($entity instanceof BiayaPendaftaran) {
                    $feeamount += $entity->getNominal();
                }
            }
        } else {
            $feeamount = 0;
        }

        return $feeamount;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.payments', array(), 'navigations')][$this->get('translator')->trans('links.applicant.payment', array(), 'navigations')]->setCurrent(true);
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
