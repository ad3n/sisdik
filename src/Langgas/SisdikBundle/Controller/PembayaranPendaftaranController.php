<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\LayananSms;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PembayaranPendaftaran;
use Langgas\SisdikBundle\Entity\Penjurusan;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\RestitusiPendaftaran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Util\Messenger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pembayaran-biaya-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PembayaranPendaftaranController extends Controller
{
    /**
     * @Route("/", name="pembayaran_biaya_pendaftaran__daftar")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchkey = '';
        $tampilkanTercari = false;

        $searchform = $this->createForm('sisdik_caripembayarbiayapendaftaran');

        $pendaftarTotal = $em->createQueryBuilder()
            ->select('COUNT(siswa.id)')
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
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.melaluiProsesPendaftaran = :melaluiProsesPendaftaran')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('melaluiProsesPendaftaran', true)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $querybuilder
                ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                ->leftJoin('pembayaran.transaksiPembayaranPendaftaran', 'transaksi')
                ->leftJoin('siswa.restitusiPendaftaran', 'restitusi')
            ;

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere(
                        'siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorPendaftaran LIKE :nomorpendaftaran '
                        .' OR siswa.keterangan LIKE :keterangan '
                        .' OR sekolahasal.nama LIKE :sekolahasal '
                        .' OR transaksi.nomorTransaksi = :nomortransaksi '
                    )
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorpendaftaran', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('sekolahasal', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomortransaksi', $searchdata['searchkey'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['nopayment'] === true) {
                $querybuilder
                    ->andWhere("transaksi.nominalPembayaran IS NULL")
                    ->andWhere('siswa.gelombang IS NOT NULL')
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['todayinput'] === true) {
                $currentdate = new \DateTime();

                $querybuilder
                    ->andWhere("siswa.waktuSimpan BETWEEN :datefrom AND :dateto")
                    ->setParameter('datefrom', $currentdate->format('Y-m-d').' 00:00:00')
                    ->setParameter('dateto', $currentdate->format('Y-m-d').' 23:59:59')
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['nopayment'] === false && $searchdata['notsettled'] === true) {
                $querybuilder
                    ->groupBy('siswa.id')
                    ->having("SUM(pembayaran.nominalTotalTransaksi) < (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['adaRestitusi'] === true) {
                $querybuilder
                    ->groupBy('siswa.id')
                    ->having("SUM(restitusi.nominalRestitusi) > 0")
                ;

                $tampilkanTercari = true;
            }
        }

        $qbTercari = clone $querybuilder;
        $pendaftarTercari = count($qbTercari->select('DISTINCT(siswa.id)')->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5, ['wrap-queries' => true]);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'pendaftarTotal' => $pendaftarTotal,
            'pendaftarTercari' => $pendaftarTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'searchkey' => $searchkey,
        ];
    }

    /**
     * @Route("/{sid}", name="payment_registrationfee")
     * @Method("GET")
     * @Template()
     */
    public function summaryAction($sid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->findOneBy([
            'id' => $sid,
            'melaluiProsesPendaftaran' => true,
        ]);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa yang diminta tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entities = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->findBy(['siswa' => $siswa]);

        $itemBiaya = $this->getBiayaProperties($siswa);

        if (count($itemBiaya['semua']) == count($itemBiaya['tersimpan']) && count($itemBiaya['tersimpan']) > 0 && count($itemBiaya['tersisa']) <= 0) {
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
     * @Route("/{sid}", name="payment_registrationfee_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranPendaftaran:summary.html.twig")
     */
    public function createAction($sid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau tahap penerimaan tidak valid.');
        }

        if ($this->get('security.authorization_checker')->isGranted('create', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entities = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->findBy(['siswa' => $siswa]);

        $itemBiaya = $this->getBiayaProperties($siswa);

        $entity = new PembayaranPendaftaran();
        $form = $this->createForm('sisdik_pembayaranpendaftaran', $entity);
        $form->submit($this->getRequest());

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
            $entity->setSiswa($siswa);

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
                        TransaksiPembayaranPendaftaran::tandakwitansi.$now->format('Y').$now->format('m').($nomormax + 1)
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
                $siswa->getTahun(),
                $siswa->getGelombang(),
                array_diff($itemBiaya['tersisa'], $itemBiayaTerproses),
                $siswa->getPenjurusan()
            );

            $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran() + $currentPaymentAmount;
            $totalDiscount = $siswa->getTotalPotonganPembayaranPendaftaran() + $currentDiscount;

            $totalInfoResponse = $this->forward('LanggasSisdikBundle:BiayaPendaftaran:getFeeInfoTotal', [
                'tahun' => $siswa->getTahun()->getId(),
                'gelombang' => $siswa->getGelombang()->getId(),
                'penjurusan' => $siswa->getPenjurusan() instanceof Penjurusan ? $siswa->getPenjurusan()->getId() : -999,
                'json' => 1,
            ]);
            $totalFee = json_decode($totalInfoResponse->getContent());

            if (($payableAmountRemain + $payableAmountDue) == ($totalPayment + $totalDiscount) || $totalFee->biaya == ($totalPayment + $totalDiscount)) {
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
                        'status' => true,
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'b-pendaftaran-bayar-pertama',
                                ])
                            ;
                            foreach ($layananSms as $layanan) {
                                if ($layanan instanceof LayananSms) {
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
                    'status' => true,
                ])
            ;

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                            ->findBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'c-pendaftaran-bayar',
                            ])
                        ;
                        foreach ($layananSms as $layanan) {
                            if ($layanan instanceof LayananSms) {
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
                                    $symbol.". ".number_format($currentPaymentAmount, 0, ',', '.'),
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

            if ($siswa->isLunasBiayaPendaftaran()) {
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                        'status' => true,
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ])
                            ;
                            foreach ($layananSms as $layanan) {
                                if ($layanan instanceof LayananSms) {
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
                                        $symbol.". ".number_format($totalPayment, 0, ',', '.'),
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
     * @Route("/{sid}/{id}/show", name="payment_registrationfee_show")
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

        if ($this->get('security.authorization_checker')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
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
     * Mengelola cicilan pembayaran biaya pendaftaran.
     *
     * @Route("/{sid}/{id}/edit", name="payment_registrationfee_edit")
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

        if ($this->get('security.authorization_checker')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
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
     * @Route("/{sid}/{id}/update", name="payment_registrationfee_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranPendaftaran:edit.html.twig")
     */
    public function updateAction($sid, $id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan atau gelombang tidak berisi nilai.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        // total payment start here because of the unknown behavior during submitting request
        $totalPayment = $siswa->getTotalNominalPembayaranPendaftaran();

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->find($id);
        if (!(is_object($entity) && $entity instanceof PembayaranPendaftaran)) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $transaksiSebelumnya = [];

        /* @var $transaksi TransaksiPembayaranPendaftaran */
        foreach ($entity->getTransaksiPembayaranPendaftaran() as $transaksi) {
            $tmp['sekolah'] = $transaksi->getSekolah();
            $tmp['dibuatOleh'] = $transaksi->getDibuatOleh();
            $tmp['nominalPembayaran'] = $transaksi->getNominalPembayaran();
            $tmp['keterangan'] = $transaksi->getKeterangan();

            $transaksiSebelumnya[] = $tmp;
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
        $editForm->submit($this->getRequest());

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

            foreach ($transaksiSebelumnya as $value) {
                $transaksi = $entity->getTransaksiPembayaranPendaftaran()->current();

                $transaksi->setSekolah($value['sekolah']);
                $transaksi->setDibuatOleh($value['dibuatOleh']);
                $transaksi->setNominalPembayaran($value['nominalPembayaran']);
                $transaksi->setKeterangan($value['keterangan']);

                $entity->getTransaksiPembayaranPendaftaran()->next();
            }

            $currentPaymentAmount = 0;
            $transaksi = $entity->getTransaksiPembayaranPendaftaran()->last();
            if ($transaksi instanceof TransaksiPembayaranPendaftaran) {
                $currentPaymentAmount = $transaksi->getNominalPembayaran();
                $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                $transaksi->setNomorTransaksi(
                    TransaksiPembayaranPendaftaran::tandakwitansi.$now->format('Y').$now->format('m').($nomormax + 1)
                );
            }
            $entity->setNominalTotalTransaksi($entity->getNominalTotalTransaksi() + $currentPaymentAmount);

            $payableAmountDue = $siswa->getTotalNominalBiayaPendaftaran();
            $payableAmountRemain = $this->getPayableFeesRemain(
                $siswa->getTahun(),
                $siswa->getGelombang(),
                $itemBiaya['tersisa'],
                $siswa->getPenjurusan()
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
                    'status' => true,
                ])
            ;

            foreach ($pilihanLayananSms as $pilihan) {
                if ($pilihan instanceof PilihanLayananSms) {
                    if ($pilihan->getStatus()) {
                        $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                            ->findBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'c-pendaftaran-bayar',
                            ])
                        ;
                        foreach ($layananSms as $layanan) {
                            if ($layanan instanceof LayananSms) {
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
                                    $symbol.". ".number_format($currentPaymentAmount, 0, ',', '.'),
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

            if ($siswa->isLunasBiayaPendaftaran()) {
                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                        'status' => true,
                    ])
                ;

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'jenisLayanan' => 'd-pendaftaran-bayar-lunas',
                                ])
                            ;
                            foreach ($layananSms as $layanan) {
                                if ($layanan instanceof LayananSms) {
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
                                        $symbol.". ".number_format($totalPayment, 0, ',', '.'),
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
     * @Route("/restitusi/{sid}", name="pembayaran_biaya_pendaftaran__restitusi")
     * @Template()
     */
    public function restitusiAction($sid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->findOneBy([
            'id' => $sid,
            'melaluiProsesPendaftaran' => true,
        ]);
        if (!(is_object($siswa) && $siswa instanceof Siswa && $siswa->getGelombang() instanceof Gelombang)) {
            throw $this->createNotFoundException('Entity Siswa yang diminta tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $totalBayar = $em->createQueryBuilder()
            ->select('SUM(transaksi.nominalPembayaran) AS jumlah')
            ->from('LanggasSisdikBundle:TransaksiPembayaranPendaftaran', 'transaksi')
            ->leftJoin('transaksi.pembayaranPendaftaran', 'pembayaran')
            ->where('transaksi.sekolah = :sekolah')
            ->andWhere('pembayaran.siswa = :siswa')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('siswa', $siswa)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $totalPotongan = $em->createQueryBuilder()
            ->select('SUM(pembayaran.persenPotonganDinominalkan + pembayaran.nominalPotongan) AS jumlah')
            ->from('LanggasSisdikBundle:PembayaranPendaftaran', 'pembayaran')
            ->where('pembayaran.siswa = :siswa')
            ->setParameter('siswa', $siswa)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $restitusiPendaftaran = $em->createQueryBuilder()
            ->select('restitusi')
            ->from('LanggasSisdikBundle:RestitusiPendaftaran', 'restitusi')
            ->where('restitusi.siswa = :siswa')
            ->andWhere('restitusi.sekolah = :sekolah')
            ->setParameter('siswa', $siswa)
            ->setParameter('sekolah', $sekolah)
            ->getQuery()
            ->getResult()
        ;

        $totalRestitusi = 0;
        foreach ($restitusiPendaftaran as $restitusi) {
            if ($restitusi instanceof RestitusiPendaftaran) {
                $totalRestitusi += $restitusi->getNominalRestitusi();
            }
        }

        $pembayaranPendaftaran = $em->getRepository('LanggasSisdikBundle:PembayaranPendaftaran')->findBy(['siswa' => $siswa]);

        $itemBiaya = $this->getBiayaProperties($siswa);

        $entity = new RestitusiPendaftaran();
        $entity->setSiswa($siswa);

        $form = $this->createForm('sisdik_restitusipendaftaran', $entity);

        if ($this->getRequest()->getMethod() == 'POST') {
            $form->submit($this->getRequest());

            if ($form->get('nominalRestitusi')->getData() > ($totalBayar - $totalRestitusi)) {
                $message = $this->get('translator')->trans("form.error.maximal.restitusi");
                $form->get('nominalRestitusi')->addError(new FormError($message));
            }

            if ($form->isValid()) {
                $entity->setSekolah($sekolah);
                $entity->setSiswa($siswa);
                $entity->setDibuatOleh($this->getUser());

                $now = new \DateTime();
                $qbmaxnum = $em->createQueryBuilder()
                    ->select('MAX(restitusi.nomorUrutTransaksiPertahun)')
                    ->from('LanggasSisdikBundle:RestitusiPendaftaran', 'restitusi')
                    ->where("YEAR(restitusi.waktuSimpan) = :tahunsimpan")
                    ->andWhere('restitusi.sekolah = :sekolah')
                    ->setParameter('tahunsimpan', $now->format('Y'))
                    ->setParameter('sekolah', $sekolah)
                ;
                $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

                $entity->setNomorUrutTransaksiPertahun($nomormax + 1);
                $entity->setNomorTransaksi(
                    RestitusiPendaftaran::tandakwitansi.$now->format('Y').($nomormax + 1)
                );

                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.restitusi.berhasil.disimpan'))
                ;

                return $this->redirect($this->generateUrl('pembayaran_biaya_pendaftaran__restitusi', [
                    'sid' => $sid,
                ]));
            }
        }

        return [
            'siswa' => $siswa,
            'pembayaranPendaftaran' => $pembayaranPendaftaran,
            'totalBayar' => $totalBayar,
            'totalPotongan' => $totalPotongan,
            'restitusiPendaftaran' => $restitusiPendaftaran,
            'totalRestitusi' => $totalRestitusi,
            'itemBiayaTersimpan' => $itemBiaya['tersimpan'],
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/total-restitusi/{siswa}/{json}", name="total_restitusi_siswa")
     */
    public function totalRestitusiAction($siswa, $json = 0)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $totalRestitusi = $em->createQueryBuilder()
            ->select('SUM(restitusi.nominalRestitusi)')
            ->from('LanggasSisdikBundle:RestitusiPendaftaran', 'restitusi')
            ->where('restitusi.siswa = :siswa')
            ->andWhere('restitusi.sekolah = :sekolah')
            ->setParameter('siswa', $siswa)
            ->setParameter('sekolah', $sekolah)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($json == 1) {
            $string = json_encode([
                "nominal" => $totalRestitusi,
            ]);

            return new Response($string, 200, [
                'Content-Type' => 'application/json',
            ]);
        } else {
            return new Response($totalRestitusi);
        }
    }

    /**
     * Mengambil identitas biaya pendaftaran seorang siswa.
     *
     * @param Siswa $siswa
     *
     * @return
     *                     array['semua'] array id biaya pendaftaran seluruhnya<br>
     *                     array['tersimpan'] array id biaya pendaftaran tersimpan<br>
     *                     array['tersisa'] array id biaya pendaftaran tersisa<br>
     */
    private function getBiayaProperties(Siswa $siswa)
    {
        $em = $this->getDoctrine()->getManager();

        if ($siswa->getPenjurusan() instanceof Penjurusan) {
            $biayaPendaftaran = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                ->orderBy('biaya.urutan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->setParameter('gelombang', $siswa->getGelombang())
                ->setParameter('penjurusan', $siswa->getPenjurusan())
                ->getQuery()
                ->getResult()
            ;
        } else {
            $biayaPendaftaran = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL')
                ->orderBy('biaya.urutan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->setParameter('gelombang', $siswa->getGelombang())
                ->getQuery()
                ->getResult()
            ;
        }

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
     * Mengambil jumlah biaya pendaftaran yang tersisa.
     *
     * @param Tahun      $tahun
     * @param Gelombang  $gelombang
     * @param array      $remainfee
     * @param Penjurusan $penjurusan
     *
     * @return integer
     */
    private function getPayableFeesRemain(Tahun $tahun, Gelombang $gelombang, array $remainfee, Penjurusan $penjurusan = null)
    {
        $em = $this->getDoctrine()->getManager();

        $feeamount = 0;

        if (is_array($remainfee) && count($remainfee) > 0) {
            if ($penjurusan instanceof Penjurusan) {
                $querybuilder = $em->createQueryBuilder()
                    ->select('biaya')
                    ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                    ->where('biaya.tahun = :tahun')
                    ->andWhere('biaya.gelombang = :gelombang')
                    ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                    ->andWhere('biaya.id IN (?1)')
                    ->setParameter("tahun", $tahun)
                    ->setParameter("gelombang", $gelombang)
                    ->setParameter('penjurusan', $penjurusan)
                    ->setParameter(1, $remainfee)
                ;
            } else {
                $querybuilder = $em->createQueryBuilder()
                    ->select('biaya')
                    ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                    ->where('biaya.tahun = :tahun')
                    ->andWhere('biaya.gelombang = :gelombang')
                    ->andWhere('biaya.penjurusan IS NULL')
                    ->andWhere('biaya.id IN (?1)')
                    ->setParameter("tahun", $tahun)
                    ->setParameter("gelombang", $gelombang)
                    ->setParameter(1, $remainfee)
                ;
            }

            $entities = $querybuilder->getQuery()->getResult();

            foreach ($entities as $entity) {
                if ($entity instanceof BiayaPendaftaran) {
                    $feeamount += $entity->getNominal();
                }
            }
        }

        return $feeamount;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.pembayaran.biaya.pendaftaran', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
