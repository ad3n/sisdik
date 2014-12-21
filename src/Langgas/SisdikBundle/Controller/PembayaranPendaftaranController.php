<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\LayananSmsPendaftaran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\PembayaranPendaftaran;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pembayaran-pendaftaran/{sid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PembayaranPendaftaranController extends Controller
{
    /**
     * @Route("/", name="payment_registrationfee")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entities = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->findBy(['siswa' => $siswa]);

        $itemBiaya = $this->getBiayaProperties($siswa);

        if (count($itemBiaya['semua']) == count($itemBiaya['tersimpan']) && count($itemBiaya['tersimpan']) > 0) {
            return [
                'entities' => $entities,
                'siswa' => $siswa,
                'itemBiayaSemua' => $itemBiaya['semua'],
                'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
                'itemBiayaTersisa' => $itemBiaya['tersisa'],
            ];
        } else {
            $entity = new PembayaranPendaftaran();
            $entity->setJenisPotongan("nominal");

            foreach ($itemBiaya['tersisa'] as $id) {
                $biaya = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

                $daftarBiaya = new DaftarBiayaPendaftaran();
                $daftarBiaya->setBiayaPendaftaran($biaya);
                $daftarBiaya->setNama($biaya->getJenisbiaya()->getNama());
                $daftarBiaya->setNominal($biaya->getNominal());

                $entity->getDaftarBiayaPendaftaran()->add($daftarBiaya);
            }

            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);
            $entity->setSiswa($siswa);

            $form = $this->createForm('sisdik_pembayaranpendaftaran', $entity);

            return [
                'entities' => $entities,
                'siswa' => $siswa,
                'itemBiayaSemua' => $itemBiaya['semua'],
                'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
                'itemBiayaTersisa' => $itemBiaya['tersisa'],
                'form' => $form->createView(),
            ];
        }
    }

    /**
     * @Route("/", name="payment_registrationfee_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranPendaftaran:index.html.twig")
     */
    public function createAction(Request $request, $sid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entities = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->findBy(['siswa' => $siswa]);

        $itemBiaya = $this->getBiayaProperties($siswa);

        $entity = new PembayaranPendaftaran();
        $form = $this->createForm('sisdik_pembayaranpendaftaran', $entity);
        $form->submit($request);

        // periksa apakah item pembayaran yang akan dimasukkan telah ada di database
        // ini untuk mencegah input ganda
        $formDaftarBiayaPendaftaran = $form->get('daftarBiayaPendaftaran')->getData();
        foreach ($formDaftarBiayaPendaftaran as $item) {
            if ($item instanceof DaftarBiayaPendaftaran) {
                if (in_array($item->getBiayaPendaftaran()->getId(), $itemBiaya['tersimpan'])) {
                    $this
                        ->get('session')
                        ->getFlashBag()
                        ->add('error', $this->get('translator')->trans('alert.registrationfee.is.inserted'))
                    ;

                    return $this->redirect($this->generateUrl('payment_registrationfee', [
                        'sid' => $sid,
                    ]));
                }
            }
        }

        if ($form->isValid()) {
            $now = new \DateTime();
            $qbmaxnum = $em->createQueryBuilder()
                ->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                ->andWhere('transaksi.sekolah = :sekolah')
                ->setParameter('tahunsimpan', $now->format('Y'))
                ->setParameter('bulansimpan', $now->format('m'))
                ->setParameter('sekolah', $sekolah)
            ;
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

            $currentPaymentAmount = 0;
            foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                    $currentPaymentAmount = $transaksi->getNominalPembayaran();
                    $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                    $transaksi->setNomorTransaksi(
                        TransaksiPembayaranPendaftaran::tandakwitansi . $now->format('Y') . $now->format('m') . ($nomormax + 1)
                    );
                }
            }
            $entity->setNominalTotalTransaksi($entity->getNominalTotalTransaksi() + $currentPaymentAmount);

            $nominalBiaya = 0;
            $itemBiayaTerproses = [];
            foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                if ($biaya instanceof DaftarBiayaPendaftaran) {
                    if (!$biaya->isTerpilih()) {
                        $entity->getDaftarBiayaPendaftaran()->removeElement($biaya);
                        continue;
                    }
                    $nominalBiaya += $biaya->getNominal();
                    $itemBiayaTerproses[] = $biaya->getBiayaPendaftaran()->getId();

                    $biayaPendaftaranTmp = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
                        ->find($biaya->getBiayaPendaftaran()->getId())
                    ;
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
            $payableAmountRemain = $this->getPayableFeesRemain(
                $siswa->getTahun()->getId(), $siswa->getGelombang()->getId(),
                array_diff($itemBiaya['tersisa'], $itemBiayaTerproses)
            );

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

            $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                ->findOneBy([
                    'sekolah' => $sekolah,
                ])
            ;

            if (count($itemBiaya['tersimpan']) == 0) {
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'b-pendaftaran-bayar-pertama',
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'b-pendaftaran-bayar-pertama',
                                ])
                            ;
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

                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(), $tekstemplate);
                                    $tekstemplate = str_replace("%nomor-pendaftaran%", $siswa->getNomorPendaftaran(), $tekstemplate);
                                    $tekstemplate = str_replace("%tahun%", $siswa->getTahun()->getTahun(), $tekstemplate);
                                    $tekstemplate = str_replace("%gelombang%", $siswa->getGelombang()->getNama(), $tekstemplate);

                                    if ($ponselOrtuWali != "") {
                                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
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

            $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                ->findBy([
                    'sekolah' => $sekolah,
                    'jenisLayanan' => 'c-pendaftaran-bayar',
                ])
            ;

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSmsPendaftaran = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                            ->findBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'c-pendaftaran-bayar',
                            ])
                        ;
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

                                $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(), $tekstemplate);

                                $nomorTransaksi = "";
                                $em->refresh($entity);
                                foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                                    if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                                        $em->refresh($transaksi);
                                        $nomorTransaksi = $transaksi->getNomorTransaksi();
                                    }
                                }
                                $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi, $tekstemplate);

                                $counter = 1;
                                $daftarBiayaDibayar = [];
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                                    if ($counter > 3) {
                                        $daftarBiayaDibayar[] = $this->get('translator')->trans('dll');
                                        break;
                                    }
                                    $daftarBiayaDibayar[] = $biaya->getNama();
                                    $counter++;
                                }
                                $tekstemplate = str_replace("%daftar-biaya%", (implode(", ", $daftarBiayaDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace(
                                    "%besar-pembayaran%",
                                    $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                    $tekstemplate
                                );

                                if ($ponselOrtuWali != "") {
                                    $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
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
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ])
                            ;
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

                                    $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali, $tekstemplate);
                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(), $tekstemplate);

                                    $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                                    $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                    $tekstemplate = str_replace(
                                        "%total-pembayaran%",
                                        $symbol . ". " . number_format($totalPayment, 0, ',', '.'),
                                        $tekstemplate
                                    );

                                    if ($ponselOrtuWali != "") {
                                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
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

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.payment.registration.inserted'))
            ;

            return $this->redirect($this->generateUrl('payment_registrationfee', [
                'sid' => $sid,
            ]));
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.payment.registration.fail.insert'))
        ;

        return [
            'entities' => $entities,
            'siswa' => $siswa,
            'itemBiayaSemua' => $itemBiaya['semua'],
            'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
            'itemBiayaTersisa' => $itemBiaya['tersisa'],
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/show", name="payment_registrationfee_show")
     * @Template()
     */
    public function showAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->find($id);
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

        $transaksiPembayaran = $em->getRepository('LanggasSisdikBundle:TransaksiPembayaranPendaftaran')
            ->findBy([
                'pembayaranPendaftaran' => $id,
            ], [
                'waktuSimpan' => 'ASC',
            ])
        ;

        return [
            'siswa' => $siswa,
            'entity' => $entity,
            'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
            'transaksiPembayaran' => $transaksiPembayaran,
            'nominalBiaya' => $nominalBiaya,
            'adaPotongan' => $adaPotongan,
            'jenisPotongan' => $jenisPotongan,
            'nominalPotongan' => $nominalPotongan,
            'persenPotongan' => $persenPotongan,
        ];
    }

    /**
     * Mengelola cicilan pembayaran biaya pendaftaran
     *
     * @Route("/{id}/edit", name="payment_registrationfee_edit")
     * @Template()
     */
    public function editAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

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

        if ($totalNominalTransaksiSebelumnya == ($nominalBiaya - $nominalPotongan) && $totalNominalTransaksiSebelumnya > 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registrationfee.paidoff'));
        } else {
            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);

            $editForm = $this->createForm('sisdik_pembayaranpendaftarancicilan', $entity);

            return [
                'siswa' => $siswa,
                'entity' => $entity,
                'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                'nominalBiaya' => $nominalBiaya,
                'adaPotongan' => $adaPotongan,
                'jenisPotongan' => $jenisPotongan,
                'nominalPotongan' => $nominalPotongan,
                'persenPotongan' => $persenPotongan,
                'edit_form' => $editForm->createView(),
            ];
        }
    }

    /**
     * @Route("/{id}/update", name="payment_registrationfee_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        // total payment start here because of the unknown behavior during submitting request
        $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran();

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $itemBiaya = $this->getBiayaProperties($siswa);

        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

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

        if ($totalNominalTransaksiSebelumnya == ($nominalBiaya - $nominalPotongan) && $totalNominalTransaksiSebelumnya > 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registrationfee.paidoff'));
        }

        $editForm = $this->createForm('sisdik_pembayaranpendaftarancicilan', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $now = new \DateTime();

            $qbmaxnum = $em->createQueryBuilder()
                ->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
                ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                ->andWhere('transaksi.sekolah = :sekolah')
                ->setParameter('tahunsimpan', $now->format('Y'))
                ->setParameter('bulansimpan', $now->format('m'))
                ->setParameter('sekolah', $sekolah)
            ;
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

            $currentPaymentAmount = 0;
            $transaksi = $entity->getTransaksiPembayaranPendaftaran()->last();
            if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                $currentPaymentAmount = $transaksi->getNominalPembayaran();
                $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                $transaksi->setNomorTransaksi(
                    TransaksiPembayaranPendaftaran::tandakwitansi . $now->format('Y') . $now->format('m') . ($nomormax + 1)
                );
            }
            $entity->setNominalTotalTransaksi($entity->getNominalTotalTransaksi() + $currentPaymentAmount);

            $payableAmountDue = $siswa->getTotalNominalBiayaPendaftaran();
            $payableAmountRemain = $this->getPayableFeesRemain(
                $siswa->getTahun()->getId(),
                $siswa->getGelombang()->getId(),
                $itemBiaya['tersisa']
            );

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

            $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                ->findOneBy([
                    'sekolah' => $sekolah,
                ])
            ;

            $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                ->findBy([
                    'sekolah' => $sekolah,
                    'jenisLayanan' => 'c-pendaftaran-bayar',
                ])
            ;

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSmsPendaftaran = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                            ->findBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'c-pendaftaran-bayar',
                            ])
                        ;
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

                                $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(), $tekstemplate);

                                $nomorTransaksi = "";
                                $em->refresh($entity);
                                foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
                                    if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                                        $em->refresh($transaksi);
                                        $nomorTransaksi = $transaksi->getNomorTransaksi();
                                    }
                                }
                                $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi, $tekstemplate);

                                $daftarBiayaDibayar = [];
                                foreach ($entity->getDaftarBiayaPendaftaran() as $biaya) {
                                    $daftarBiayaDibayar[] = $biaya->getNama();
                                }
                                $tekstemplate = str_replace("%daftar-biaya%", (implode(", ", $daftarBiayaDibayar)), $tekstemplate);

                                $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                                $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                $tekstemplate = str_replace(
                                    "%besar-pembayaran%",
                                    $symbol . ". " . number_format($currentPaymentAmount, 0, ',', '.'),
                                    $tekstemplate
                                );

                                if ($ponselOrtuWali != "") {
                                    $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
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
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ])
                            ;
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

                                    $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali, $tekstemplate);
                                    $tekstemplate = str_replace("%nama-pendaftar%", $siswa->getNamaLengkap(), $tekstemplate);

                                    $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                                    $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                                    $tekstemplate = str_replace(
                                        "%total-pembayaran%",
                                        $symbol . ". " . number_format($totalPayment, 0, ',', '.'),
                                        $tekstemplate
                                    );

                                    if ($ponselOrtuWali != "") {
                                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
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

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.payment.registration.mortgage.updated'))
            ;

            return $this->redirect($this->generateUrl('payment_registrationfee_show', [
                'sid' => $sid,
                'id' => $id,
            ]));
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.payment.registration.mortgage.fail.insert'))
        ;

        return [
            'siswa' => $siswa,
            'entity' => $entity,
            'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
            'nominalBiaya' => $nominalBiaya,
            'adaPotongan' => $adaPotongan,
            'jenisPotongan' => $jenisPotongan,
            'nominalPotongan' => $nominalPotongan,
            'persenPotongan' => $persenPotongan,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Mengambil identitas biaya pendaftaran seorang siswa
     *
     * @param Siswa $siswa
     * @return
     *                     array['semua'] array id biaya pendaftaran seluruhnya<br>
     *                     array['tersimpan'] array id biaya pendaftaran tersimpan<br>
     *                     array['tersisa'] array id biaya pendaftaran tersisa<br>
     */
    private function getBiayaProperties(Siswa $siswa)
    {
        $em = $this->getDoctrine()->getManager();

        $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
            ->findBy([
                'tahun' => $siswa->getTahun(),
                'gelombang' => $siswa->getGelombang(),
            ], [
                'urutan' => 'ASC',
            ])
        ;

        $idBiayaSemua = [];
        foreach ($biayaPendaftaran as $biaya) {
            if ($biaya instanceof BiayaPendaftaran) {
                $idBiayaSemua[] = $biaya->getId();
            }
        }

        $querybuilder1 = $em->createQueryBuilder()
            ->select('daftar')
            ->from('LanggasSisdikBundle:DaftarBiayaPendaftaran', 'daftar')
            ->leftJoin('daftar.biayaPendaftaran', 'biaya')
            ->leftJoin('daftar.pembayaranPendaftaran', 'pembayaran')
            ->where('pembayaran.siswa = :siswa')
            ->orderBy('biaya.urutan', 'ASC')
            ->setParameter('siswa', $siswa)
        ;
        $daftarBiaya = $querybuilder1->getQuery()->getResult();
        $idBiayaTersimpan = [];
        foreach ($daftarBiaya as $daftar) {
            if ($daftar instanceof DaftarBiayaPendaftaran) {
                $idBiayaTersimpan[] = $daftar->getBiayaPendaftaran()->getId();
            }
        }

        $idBiayaSisa = array_diff($idBiayaSemua, $idBiayaTersimpan);

        return [
            'semua' => $idBiayaSemua,
            'tersimpan' => $idBiayaTersimpan,
            'tersisa' => $idBiayaSisa,
        ];
    }

    /**
     * Mengambil jumlah biaya pendaftaran yang tersisa
     */
    private function getPayableFeesRemain($tahun, $gelombang, $remainfee)
    {
        $em = $this->getDoctrine()->getManager();

        if (is_array($remainfee) && count($remainfee) > 0) {
            $querybuilder = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.id IN (?1)')
                ->setParameter("tahun", $tahun)
                ->setParameter("gelombang", $gelombang)
                ->setParameter(1, $remainfee)
            ;
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

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.applicant.payment', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
