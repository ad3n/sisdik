<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaRutin;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\LayananSms;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PembayaranRutin;
use Langgas\SisdikBundle\Entity\Penjurusan;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranRutin;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Util\Messenger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\TranslationBundle\Annotation\Ignore;

/**
 * @Route("/pembayaran-biaya-rutin")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PembayaranBiayaRutinController extends Controller
{
    const PERIODE_AWAL = 3;
    const PERIODE_TAMBAH = 10;

    /**
     * @Route("/", name="pembayaran_biaya_rutin__daftar")
     * @Method("GET")
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

        $searchform = $this->createForm('sisdik_caripembayarbiayarutin');

        $siswaTotal = $em->createQueryBuilder()
            ->select('COUNT(siswa.id)')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('siswa.nomorIndukSistem', 'DESC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
        ;

        $searchform->submit($this->getRequest());

        if ($searchform->get('adaTunggakan')->getData() === true) {
            if (is_null($searchform->get('batasanPencarianTunggakan')->getData())) {
                $message = $this->get('translator')->trans("pencarian.tunggakan.biaya.berulang.perlu.batasan");
                $searchform->get('adaTunggakan')->addError(new FormError($message));
            }
        }

        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $querybuilder
                ->leftJoin('siswa.pembayaranRutin', 'pembayaran')
                ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
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
                        .' OR siswa.nomorInduk LIKE :nomorInduk '
                        .' OR siswa.nomorIndukSistem LIKE :identitas '
                        .' OR siswa.keteranganPembayaranRutin LIKE :keterangan '
                        .' OR transaksi.nomorTransaksi = :nomortransaksi '
                    )
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomorInduk', "%{$searchdata['searchkey']}%")
                    ->setParameter('identitas', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomortransaksi', $searchdata['searchkey'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['adaTunggakan'] === true) {
                $batas = intval($searchdata['batasanPencarianTunggakan']) > 15 ? 15 : intval($searchdata['batasanPencarianTunggakan']);
                $querybuilder->setMaxResults($batas);

                $qbTmp = clone $querybuilder;
                $hasilTmp = $qbTmp
                    ->select('siswa.id')
                    ->getQuery()
                    ->getArrayResult()
                ;

                $daftarSiswaTercari = array_map('current', $hasilTmp);
                $tanggalSekarang = new \DateTime();
                $daftarPenunggak = [];

                if (count($daftarSiswaTercari) > 0) {
                    foreach ($daftarSiswaTercari as $id) {
                        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);
                        if (!($siswa instanceof Siswa)) {
                            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
                        }

                        if ($this->memilikiTunggakan($siswa, $tanggalSekarang)) {
                            $daftarPenunggak[] = $id;
                        }
                    }

                    if (count($daftarPenunggak) > 0) {
                        $querybuilder
                            ->andWhere('siswa.id IN (?1)')
                            ->setParameter(1, $daftarPenunggak)
                        ;
                    } else {
                        $querybuilder->andWhere('siswa.id IS NULL');
                    }
                }

                $tampilkanTercari = true;
            }
        }

        $qbTercari = clone $querybuilder;
        $siswaTercari = count($qbTercari->select('DISTINCT(siswa.id)')->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'siswaTotal' => $siswaTotal,
            'siswaTercari' => $siswaTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'searchkey' => $searchkey,
        ];
    }

    /**
     * Menampilkan ringkasan informasi daftar biaya berulang yang wajib dibayar per siswa
     *
     * @Route("biaya-per-siswa/{sid}", name="pembayaran_biaya_rutin__ringkasan_biaya")
     * @Method("GET")
     * @Template()
     */
    public function biayaPerSiswaAction($sid)
    {
        $em = $this->getDoctrine()->getManager();

        /* @var $translator Translator */
        $translator = $this->get('translator');

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        if ($siswa->getPenjurusan() instanceof Penjurusan) {
            $daftarBiayaRutin = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                ->orderBy('biaya.perulangan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->setParameter('penjurusan', $siswa->getPenjurusan())
                ->getQuery()
                ->getResult()
            ;
        } else {
            $daftarBiayaRutin = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.penjurusan IS NULL')
                ->orderBy('biaya.perulangan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->getQuery()
                ->getResult()
            ;
        }

        $daftarPerulangan = BiayaRutin::getDaftarPerulangan();
        $daftarBulan = BiayaRutin::getDaftarNamaBulan();
        $daftarHari = JadwalKehadiran::getNamaHari();
        $tanggalSekarang = new \DateTime();
        $bulanSekarang = $tanggalSekarang->format('n');

        /* @var $bedaWaktu \DateInterval */
        $bedaWaktu = $tanggalSekarang->diff($siswa->getPembiayaanSejak());

        $dataBiaya = [];
        foreach ($daftarBiayaRutin as $biaya) {
            /* @var $biaya BiayaRutin */

            $jumlahWajibBayar = 0;

            $pembayaranLunas = $em->createQueryBuilder()
                ->select('COUNT(DISTINCT pembayaran.id) AS jumlah')
                ->from('LanggasSisdikBundle:PembayaranRutin', 'pembayaran')
                ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
                ->where('pembayaran.siswa = :siswa')
                ->andWhere('pembayaran.biayaRutin = :biayaRutin')
                ->setParameter('siswa', $siswa)
                ->setParameter('biayaRutin', $biaya)
                ->groupBy('pembayaran.id')
                ->having('SUM(transaksi.nominalPembayaran) >= (SUM(DISTINCT pembayaran.nominalBiaya) - (SUM(DISTINCT pembayaran.nominalPotongan) + SUM(DISTINCT pembayaran.persenPotonganDinominalkan)))')
                ->getQuery()
                ->getResult()
            ;

            if (count($pembayaranLunas) == 0) {
                $pembayaranLunas[0]['jumlah'] = 0;
            }

            $jumlahPembayaran = 0;
            if (is_array($pembayaranLunas)) {
                foreach ($pembayaranLunas as $pembayaran) {
                    $jumlahPembayaran += $pembayaran['jumlah'];
                }
            }

            $pembayaranTerbaru = $em->createQueryBuilder()
                ->select('transaksi')
                ->from('LanggasSisdikBundle:TransaksiPembayaranRutin', 'transaksi')
                ->leftJoin('transaksi.pembayaranRutin', 'pembayaran')
                ->where('pembayaran.siswa = :siswa')
                ->andWhere('pembayaran.biayaRutin = :biayaRutin')
                ->orderBy('transaksi.waktuSimpan', 'DESC')
                ->setParameter('siswa', $siswa)
                ->setParameter('biayaRutin', $biaya)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

            $jatuhTempo = $translator->trans(/** @Ignore */ $daftarPerulangan[$biaya->getPerulangan()]);

            if ($biaya->getBulanAwal() == 0) {
                $tmpTanggal = new \DateTime($tanggalSekarang->format('Y').'-01-01');
            } else {
                $tmpTanggal = new \DateTime($tanggalSekarang->format('Y').'-'.$biaya->getBulanAwal().'-01');
            }

            $tmpHariKe = ($biaya->getBulananHariKe() && $biaya->getBulananHariKe() <= 28) ? $biaya->getBulananHariKe() : '01';
            $tanggalAwalBayar = new \DateTime($siswa->getPembiayaanSejak()->format('Y-m-').$tmpHariKe);

            $bedaBulan = abs($biaya->getBulanAwal() - $siswa->getPembiayaanSejak()->format('n'));

            switch ($biaya->getPerulangan()) {
                case 'a-harian':
                    $jumlahWajibBayar = $bedaWaktu->format("%a");

                    break;
                case 'b-mingguan':
                    $jumlahWajibBayar = floor($bedaWaktu->format("%a") / 7);

                    $jatuhTempo .= ': '.$translator->trans(/** @Ignore */ $daftarHari[$biaya->getMingguanHariKe()]);

                    break;
                case 'c-bulanan':
                    $jumlahWajibBayar = $bedaWaktu->format("%m");

                    if ($bedaWaktu->format("%d") >= $biaya->getBulananHariKe()) {
                        $jumlahWajibBayar++;
                    }

                    $jatuhTempo .= ': '.$translator->trans('tanggal')
                        .' '
                        .$biaya->getBulananHariKe()
                    ;

                    break;
                case 'd-triwulan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 3);

                    if ($bedaWaktu->format('%m') % 3 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    $jatuhTempo .= ': '.$translator->trans('tanggal')
                        .' '
                        .$biaya->getBulananHariKe()
                    ;

                    $jatuhTempo .= ' (';

                    $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+3 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+3 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+3 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $jatuhTempo .= ')';

                    break;
                case 'e-caturwulan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 4);

                    if ($bedaWaktu->format('%m') % 4 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    $jatuhTempo .= ': '.$translator->trans('tanggal')
                        .' '
                        .$biaya->getBulananHariKe()
                    ;

                    $jatuhTempo .= ' (';

                    $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+4 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+4 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $jatuhTempo .= ')';

                    break;
                case 'f-semester':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 6);

                    if ($bedaWaktu->format('%m') % 6 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    $jatuhTempo .= ': '.$translator->trans('tanggal')
                        .' '
                        .$biaya->getBulananHariKe()
                    ;

                    $jatuhTempo .= ' (';

                    $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $tmpTanggal->modify('+6 months');
                    $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                    $jatuhTempo .= ')';

                    break;
                case 'g-tahunan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    if ($bedaWaktu->format("%r%a") < 0) {
                        $jumlahWajibBayar++;
                    }

                    $jatuhTempo .= ': '.$translator->trans('tanggal')
                        .' '
                        .$biaya->getBulananHariKe()
                        .' '
                        .$translator->trans(/** @Ignore */ $daftarBulan[$biaya->getBulanAwal()])
                    ;

                    break;
            }

            $dataBiaya[] = [
                'biaya' => $biaya,
                'jatuhTempo' => $jatuhTempo,
                'pembayaranTerbaru' => $pembayaranTerbaru,
                'jumlahPembayaran' => $jumlahPembayaran,
                'jumlahWajibBayar' => $jumlahWajibBayar,
            ];
        }

        return [
            'siswa' => $siswa,
            'dataBiaya' => $dataBiaya,
        ];
    }

    /**
     * @Route("/{sid}/{bid}", name="pembayaran_biaya_rutin__summary")
     * @Template()
     */
    public function summaryAction($sid, $bid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!($siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $biaya = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($bid);
        if (!($biaya instanceof BiayaRutin)) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $pembayaranRutin = $em->getRepository('LanggasSisdikBundle:PembayaranRutin')
            ->findBy([
                'siswa' => $siswa,
                'biayaRutin' => $biaya,
            ], [
                'waktuSimpan' => 'DESC',
            ], self::PERIODE_AWAL)
        ;

        $daftarPerulangan = BiayaRutin::getDaftarPerulangan();
        $daftarBulan = BiayaRutin::getDaftarNamaBulan();
        $daftarHari = JadwalKehadiran::getNamaHari();
        $tanggalSekarang = new \DateTime();
        $bulanSekarang = $tanggalSekarang->format('n');

        /* @var $bedaWaktu \DateInterval */
        $bedaWaktu = $tanggalSekarang->diff($siswa->getPembiayaanSejak());

        $pembayaranLunas = $em->createQueryBuilder()
            ->select('COUNT(DISTINCT pembayaran.id) AS jumlah')
            ->from('LanggasSisdikBundle:PembayaranRutin', 'pembayaran')
            ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
            ->where('pembayaran.siswa = :siswa')
            ->andWhere('pembayaran.biayaRutin = :biayaRutin')
            ->setParameter('siswa', $siswa)
            ->setParameter('biayaRutin', $biaya)
            ->groupBy('pembayaran.id')
            ->having('SUM(transaksi.nominalPembayaran) >= (SUM(DISTINCT pembayaran.nominalBiaya) - (SUM(DISTINCT pembayaran.nominalPotongan) + SUM(DISTINCT pembayaran.persenPotonganDinominalkan)))')
            ->getQuery()
            ->getResult()
        ;

        if (count($pembayaranLunas) == 0) {
            $pembayaranLunas[0]['jumlah'] = 0;
        }

        $jumlahPembayaran = 0;
        if (is_array($pembayaranLunas)) {
            foreach ($pembayaranLunas as $pembayaran) {
                $jumlahPembayaran += $pembayaran['jumlah'];
            }
        }

        $pembayaranBelumLunas = $em->createQueryBuilder()
            ->select('DISTINCT pembayaran.id')
            ->from('LanggasSisdikBundle:PembayaranRutin', 'pembayaran')
            ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
            ->where('pembayaran.siswa = :siswa')
            ->andWhere('pembayaran.biayaRutin = :biayaRutin')
            ->orderBy('pembayaran.waktuSimpan', 'DESC')
            ->setParameter('siswa', $siswa)
            ->setParameter('biayaRutin', $biaya)
            ->groupBy('pembayaran.id')
            ->having('SUM(transaksi.nominalPembayaran) < (SUM(DISTINCT pembayaran.nominalBiaya) - (SUM(DISTINCT pembayaran.nominalPotongan) + SUM(DISTINCT pembayaran.persenPotonganDinominalkan)))')
            ->getQuery()
            ->getResult()
        ;
        $jumlahPembayaranBelumLunas = count($pembayaranBelumLunas);

        $jumlahWajibBayar = 0;

        $jatuhTempo = $translator->trans(/** @Ignore */ $daftarPerulangan[$biaya->getPerulangan()]);

        if ($biaya->getBulanAwal() == 0) {
            $tmpTanggal = new \DateTime($tanggalSekarang->format('Y').'-01-01');
        } else {
            $tmpTanggal = new \DateTime($tanggalSekarang->format('Y').'-'.$biaya->getBulanAwal().'-01');
        }

        $tmpHariKe = ($biaya->getBulananHariKe() && $biaya->getBulananHariKe() <= 28) ? $biaya->getBulananHariKe() : '01';
        $tanggalAwalBayar = new \DateTime($siswa->getPembiayaanSejak()->format('Y-m-').$tmpHariKe);

        $bedaBulan = abs($biaya->getBulanAwal() - $siswa->getPembiayaanSejak()->format('n'));

        switch ($biaya->getPerulangan()) {
            case 'a-harian':
                $jumlahWajibBayar = $bedaWaktu->format("%a");

                break;
            case 'b-mingguan':
                $jumlahWajibBayar = floor($bedaWaktu->format("%a") / 7);

                $jatuhTempo .= ': '.$translator->trans(/** @Ignore */ $daftarHari[$biaya->getMingguanHariKe()]);

                break;
            case 'c-bulanan':
                $jumlahWajibBayar = $bedaWaktu->format("%m");

                if ($bedaWaktu->format("%d") >= $biaya->getBulananHariKe()) {
                    $jumlahWajibBayar++;
                }

                $jatuhTempo .= ': '.$translator->trans('tanggal')
                    .' '
                    .$biaya->getBulananHariKe()
                ;

                break;
            case 'd-triwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 3);

                if ($bedaWaktu->format('%m') % 3 == 0) {
                    if ($bedaWaktu->format("%r%d") < 0) {
                        $jumlahWajibBayar++;
                    }
                }

                $jatuhTempo .= ': '.$translator->trans('tanggal')
                    .' '
                    .$biaya->getBulananHariKe()
                ;

                $jatuhTempo .= ' (';

                $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+3 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+3 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+3 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $jatuhTempo .= ')';

                break;
            case 'e-caturwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 4);

                if ($bedaWaktu->format('%m') % 4 == 0) {
                    if ($bedaWaktu->format("%r%d") < 0) {
                        $jumlahWajibBayar++;
                    }
                }

                $jatuhTempo .= ': '.$translator->trans('tanggal')
                    .' '
                    .$biaya->getBulananHariKe()
                ;

                $jatuhTempo .= ' (';

                $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+4 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+4 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $jatuhTempo .= ')';

                break;
            case 'f-semester':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 6);

                if ($bedaWaktu->format('%m') % 6 == 0) {
                    if ($bedaWaktu->format("%r%d") < 0) {
                        $jumlahWajibBayar++;
                    }
                }

                $jatuhTempo .= ': '.$translator->trans('tanggal')
                    .' '
                    .$biaya->getBulananHariKe()
                ;

                $jatuhTempo .= ' (';

                $jatuhTempo .= $translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $tmpTanggal->modify('+6 months');
                $jatuhTempo .= '/'.$translator->trans(/** @Ignore */ $daftarBulan[$tmpTanggal->format('n')]);

                $jatuhTempo .= ')';

                break;
            case 'g-tahunan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                if ($bedaWaktu->format("%r%a") < 0) {
                    $jumlahWajibBayar++;
                }

                $jatuhTempo .= ': '.$translator->trans('tanggal')
                    .' '
                    .$biaya->getBulananHariKe()
                    .' '
                    .$translator->trans(/** @Ignore */ $daftarBulan[$biaya->getBulanAwal()])
                ;

                break;
        }

        if ($this->getRequest()->getMethod() == "POST") {
            if ($siswa->getPenjurusan() instanceof Penjurusan) {
                $biayaBisaDibayar = $em->createQueryBuilder()
                    ->select('biaya')
                    ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                    ->where('biaya.id = :id')
                    ->andWhere('biaya.tahun = :tahun')
                    ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                    ->orderBy('biaya.perulangan', 'ASC')
                    ->setParameter('id', $biaya->getId())
                    ->setParameter('tahun', $siswa->getTahun())
                    ->setParameter('penjurusan', $siswa->getPenjurusan())
                    ->getQuery()
                    ->getOneOrNullResult()
                ;
            } else {
                $biayaBisaDibayar = $em->createQueryBuilder()
                    ->select('biaya')
                    ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                    ->where('biaya.id = :id')
                    ->andWhere('biaya.tahun = :tahun')
                    ->andWhere('biaya.penjurusan IS NULL')
                    ->orderBy('biaya.perulangan', 'ASC')
                    ->setParameter('id', $biaya->getId())
                    ->setParameter('tahun', $siswa->getTahun())
                    ->getQuery()
                    ->getOneOrNullResult()
                ;
            }

            if (!$biayaBisaDibayar instanceof BiayaRutin) {
                throw $this->createNotFoundException('BiayaRutin yang dipilih tidak bisa dibayar oleh siswa tersebut.');
            }

            // if ($jumlahPembayaranBelumLunas != 0) {
            //     throw new AccessDeniedException($this->get('translator')->trans('exception.pembayaran.sebelumnya.harus.lunas'));
            // }

            $entity = new PembayaranRutin();
            $form = $this->createForm('sisdik_pembayaranrutin', $entity);
            $form->submit($this->getRequest());

            $nominalBayar = 0;
            $nominalBiaya = $biaya->getNominal();
            $transaksiCollection = $form->get('transaksiPembayaranRutin')->getData();

            if ($form->get('jenisPotongan')->getData() == 'nominal') {
                $nominalBiaya = $nominalBiaya - $form->get('nominalPotongan')->getData();
            } elseif ($form->get('jenisPotongan')->getData() == 'persentase') {
                $nominalBiaya = $nominalBiaya - ($form->get('persenPotongan')->getData() / 100 * $nominalBiaya);
            }

            /* @var $transaksi TransaksiPembayaranRutin */
            foreach ($transaksiCollection as $transaksi) {
                $nominalBayar += $transaksi->getNominalPembayaran();
            }

            if ($nominalBayar > $nominalBiaya) {
                if ($form->get('jenisPotongan')->getData()) {
                    $message = $this->get('translator')->trans('shortinfo.pay.notbiggerthan.fee.discounted');
                } else {
                    $message = $this->get('translator')->trans('shortinfo.pay.notbiggerthan.fee');
                }

                $form->get('transaksiPembayaranRutin')->addError(new FormError($message));
            }

            if ($form->isValid()) {
                $entity->setSiswa($siswa);
                $entity->setBiayaRutin($biaya);
                $entity->setNominalBiaya($biaya->getNominal());
                $entity->setNamaBiaya($biaya->getJenisbiaya()->getNama());

                $biaya->setTerpakai(true);

                $now = new \DateTime();
                $entity->setTanggal($now);

                $qbmaxnum = $em->createQueryBuilder()
                    ->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                    ->from('LanggasSisdikBundle:TransaksiPembayaranRutin', 'transaksi')
                    ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                    ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                    ->andWhere('transaksi.sekolah = :sekolah')
                    ->setParameter('tahunsimpan', $now->format('Y'))
                    ->setParameter('bulansimpan', $now->format('m'))
                    ->setParameter('sekolah', $sekolah)
                ;
                $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

                $totalPayment = 0;
                $nomorTransaksi = "";
                foreach ($entity->getTransaksiPembayaranRutin() as $transaksi) {
                    if ($transaksi instanceof TransaksiPembayaranRutin) {
                        $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                        $transaksi->setNomorTransaksi(
                            TransaksiPembayaranRutin::tandakwitansi.$now->format('Y').$now->format('m').($nomormax + 1)
                        );
                        $totalPayment += $transaksi->getNominalPembayaran();
                        $nomorTransaksi = $transaksi->getNomorTransaksi();
                    }
                }

                if ($entity->getAdaPotongan() === false) {
                    $entity->setJenisPotongan(null);
                    $entity->setNominalPotongan(0);
                    $entity->setPersenPotongan(0);
                    $entity->setPersenPotonganDinominalkan(0);
                }

                if ($entity->getAdaPotongan() && $entity->getPersenPotongan() != 0) {
                    $persenPotonganDinominalkan = $entity->getNominalBiaya() * ($entity->getPersenPotongan() / 100);
                    $entity->setPersenPotonganDinominalkan($persenPotonganDinominalkan);
                    $entity->setNominalPotongan(0);
                } else {
                    $entity->setPersenPotongan(0);
                    $entity->setPersenPotonganDinominalkan(0);
                }

                $em->persist($entity);
                $em->persist($biaya);

                $em->flush();

                $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                    ])
                ;

                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'zda-biaya-rutin-bayar',
                        'status' => true,
                    ])
                ;

                if ($pilihanLayananSms instanceof PilihanLayananSms) {
                    if ($pilihanLayananSms->getStatus()) {
                        $layanan = $em->getRepository('LanggasSisdikBundle:LayananSms')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'zda-biaya-rutin-bayar',
                            ])
                        ;
                        if ($layanan instanceof LayananSms) {
                            $tekstemplate = $layanan->getTemplatesms()->getTeks();

                            $namaOrtuWali = "";
                            $ponselOrtuWali = "";
                            $orangtuaWaliAktif = $siswa->getOrangtuaWaliAktif();
                            if ($orangtuaWaliAktif instanceof OrangtuaWali) {
                                $namaOrtuWali = $orangtuaWaliAktif->getNama();
                                $ponselOrtuWali = $orangtuaWaliAktif->getPonsel();
                            }

                            $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali, $tekstemplate);
                            $tekstemplate = str_replace("%nama-siswa%", $siswa->getNamaLengkap(), $tekstemplate);
                            $tekstemplate = str_replace("%nis%", $siswa->getNomorInduk(), $tekstemplate);
                            $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi, $tekstemplate);
                            $tekstemplate = str_replace("%nama-biaya%", $entity->getNamaBiaya(), $tekstemplate);

                            $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                            $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                            $tekstemplate = str_replace(
                                "%besar-pembayaran%",
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

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.pembayaran.biaya.berulang.tersimpan'))
                ;

                return $this->redirect($this->generateUrl('pembayaran_biaya_rutin__summary', [
                    'sid' => $sid,
                    'bid' => $bid,
                ]));
            }

            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.pembayaran.biaya.berulang.gagal.disimpan'))
            ;
        }

        // if ($jumlahPembayaranBelumLunas > 0) {
        //     return [
        //         'siswa' => $siswa,
        //         'biaya' => $biaya,
        //         'pembayaranRutin' => $pembayaranRutin,
        //         'jumlahWajibBayar' => $jumlahWajibBayar,
        //         'jumlahPembayaran' => $jumlahPembayaran,
        //         'jumlahPembayaranBelumLunas' => $jumlahPembayaranBelumLunas,
        //         'jatuhTempo' => $jatuhTempo,
        //         'daftarPerulangan' => $daftarPerulangan,
        //         'daftarBulan' => $daftarBulan,
        //         'daftarHari' => $daftarHari,
        //     ];
        // } else {
        // }

        $entity = new PembayaranRutin();
        $entity->setJenisPotongan("nominal");

        $transaksiPembayaranRutin = new TransaksiPembayaranRutin();
        $entity->getTransaksiPembayaranRutin()->add($transaksiPembayaranRutin);
        $entity->setSiswa($siswa);

        $form = $this->createForm('sisdik_pembayaranrutin', $entity);

        return [
            'siswa' => $siswa,
            'biaya' => $biaya,
            'pembayaranRutin' => $pembayaranRutin,
            'jumlahWajibBayar' => $jumlahWajibBayar,
            'jumlahPembayaran' => $jumlahPembayaran,
            'jumlahPembayaranBelumLunas' => $jumlahPembayaranBelumLunas,
            'jumlahPeriode' => $jumlahPembayaran + $jumlahPembayaranBelumLunas,
            'jatuhTempo' => $jatuhTempo,
            'tanggalAwalBayar' => $tanggalAwalBayar,
            'daftarPerulangan' => $daftarPerulangan,
            'daftarBulan' => $daftarBulan,
            'daftarHari' => $daftarHari,
            'form' => $form->createView(),
            'tambahPeriode' => self::PERIODE_TAMBAH,
        ];
    }

    /**
     * Mengelola cicilan pembayaran biaya rutin
     *
     * @Route("-cicil/{sid}/{pid}", name="pembayaran_biaya_rutin__edit")
     * @Template()
     */
    public function editAction($sid, $pid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!($siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranRutin')->find($pid);
        if (!$entity instanceof PembayaranRutin) {
            throw $this->createNotFoundException('Entity PembayaranRutin tak ditemukan.');
        }

        $totalBiayaHarusDibayar = $entity->getNominalBiaya() - ($entity->getNominalPotongan() + $entity->getPersenPotonganDinominalkan());
        if ($totalBiayaHarusDibayar == $entity->getTotalNominalTransaksiPembayaranRutin()) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.periode.pembayaran.rutin.telah.lunas'));
        }

        $transaksiPembayaran = new TransaksiPembayaranRutin();
        $entity->getTransaksiPembayaranRutin()->add($transaksiPembayaran);

        $editForm = $this->createForm('sisdik_pembayaranrutincicilan', $entity);

        return [
            'siswa' => $entity->getSiswa(),
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("-cicil-ubah/{sid}/{pid}", name="pembayaran_biaya_rutin__update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranBiayaRutin:edit.html.twig")
     */
    public function updateAction($sid, $pid)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!($siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranRutin')->find($pid);
        if (!(is_object($entity) && $entity instanceof PembayaranRutin)) {
            throw $this->createNotFoundException('Entity PembayaranRutin tak ditemukan.');
        }

        $totalBiayaHarusDibayar = $entity->getNominalBiaya() - ($entity->getNominalPotongan() + $entity->getPersenPotonganDinominalkan());
        if ($totalBiayaHarusDibayar == $entity->getTotalNominalTransaksiPembayaranRutin()) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.periode.pembayaran.rutin.telah.lunas'));
        }

        $transaksiSebelumnya = [];

        /* @var $transaksi TransaksiPembayaranRutin */
        foreach ($entity->getTransaksiPembayaranRutin() as $transaksi) {
            $tmp['sekolah'] = $transaksi->getSekolah();
            $tmp['dibuatOleh'] = $transaksi->getDibuatOleh();
            $tmp['nominalPembayaran'] = $transaksi->getNominalPembayaran();
            $tmp['keterangan'] = $transaksi->getKeterangan();

            $transaksiSebelumnya[] = $tmp;
        }

        $editForm = $this->createForm('sisdik_pembayaranrutincicilan', $entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $now = new \DateTime();

            $qbmaxnum = $em->createQueryBuilder()
                ->select('MAX(transaksi.nomorUrutTransaksiPerbulan)')
                ->from('LanggasSisdikBundle:TransaksiPembayaranRutin', 'transaksi')
                ->where("YEAR(transaksi.waktuSimpan) = :tahunsimpan")
                ->andWhere("MONTH(transaksi.waktuSimpan) = :bulansimpan")
                ->andWhere('transaksi.sekolah = :sekolah')
                ->setParameter('tahunsimpan', $now->format('Y'))
                ->setParameter('bulansimpan', $now->format('m'))
                ->setParameter('sekolah', $sekolah)
            ;
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());

            foreach ($transaksiSebelumnya as $value) {
                $transaksi = $entity->getTransaksiPembayaranRutin()->current();

                $transaksi->setSekolah($value['sekolah']);
                $transaksi->setDibuatOleh($value['dibuatOleh']);
                $transaksi->setNominalPembayaran($value['nominalPembayaran']);
                $transaksi->setKeterangan($value['keterangan']);

                $entity->getTransaksiPembayaranRutin()->next();
            }

            $totalPayment = 0;
            $nomorTransaksi = "";
            $transaksi = $entity->getTransaksiPembayaranRutin()->last();
            if ($transaksi instanceof TransaksiPembayaranRutin) {
                $transaksi->setNomorUrutTransaksiPerbulan($nomormax + 1);
                $transaksi->setNomorTransaksi(
                    TransaksiPembayaranRutin::tandakwitansi.$now->format('Y').$now->format('m').($nomormax + 1)
                );
                $totalPayment += $transaksi->getNominalPembayaran();
                $nomorTransaksi = $transaksi->getNomorTransaksi();
            }

            $em->persist($entity);

            $em->flush();

            $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                ->findOneBy([
                    'sekolah' => $sekolah,
                ])
            ;

            $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'jenisLayanan' => 'zda-biaya-rutin-bayar',
                    'status' => true,
                ])
            ;

            if ($pilihanLayananSms instanceof PilihanLayananSms) {
                if ($pilihanLayananSms->getStatus()) {
                    $layanan = $em->getRepository('LanggasSisdikBundle:LayananSms')
                        ->findOneBy([
                            'sekolah' => $sekolah,
                            'jenisLayanan' => 'zda-biaya-rutin-bayar',
                        ])
                    ;
                    if ($layanan instanceof LayananSms) {
                        $tekstemplate = $layanan->getTemplatesms()->getTeks();

                        $namaOrtuWali = "";
                        $ponselOrtuWali = "";
                        $orangtuaWaliAktif = $siswa->getOrangtuaWaliAktif();
                        if ($orangtuaWaliAktif instanceof OrangtuaWali) {
                            $namaOrtuWali = $orangtuaWaliAktif->getNama();
                            $ponselOrtuWali = $orangtuaWaliAktif->getPonsel();
                        }

                        $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali, $tekstemplate);
                        $tekstemplate = str_replace("%nama-siswa%", $siswa->getNamaLengkap(), $tekstemplate);
                        $tekstemplate = str_replace("%nis%", $siswa->getNomorInduk(), $tekstemplate);
                        $tekstemplate = str_replace("%nomor-kwitansi%", $nomorTransaksi, $tekstemplate);
                        $tekstemplate = str_replace("%nama-biaya%", $entity->getNamaBiaya(), $tekstemplate);

                        $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
                        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                        $tekstemplate = str_replace(
                            "%besar-pembayaran%",
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

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.pembayaran.cicilan.biaya.berulang.terbarui'))
            ;

            return $this->redirect($this->generateUrl('pembayaran_biaya_rutin__summary', [
                'sid' => $siswa->getId(),
                'bid' => $entity->getBiayaRutin()->getId(),
            ]));
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.pembayaran.cicilan.biaya.berulang.gagal.disimpan'))
        ;

        return [
            'siswa' => $siswa,
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/ambil-periode/{sid}/{bid}/{jumlah_periode}/{periode}", name="pembayaran_biaya_rutin__periode")
     * @Method("GET")
     * @Template()
     */
    public function periodeAction($sid, $bid, $jumlah_periode, $periode)
    {
        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!($siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $biaya = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($bid);
        if (!($biaya instanceof BiayaRutin)) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        $tmpHariKe = ($biaya->getBulananHariKe() && $biaya->getBulananHariKe() <= 28) ? $biaya->getBulananHariKe() : '01';
        $tanggalAwalBayar = new \DateTime($siswa->getPembiayaanSejak()->format('Y-m-').$tmpHariKe);

        $bedaBulan = abs($biaya->getBulanAwal() - $siswa->getPembiayaanSejak()->format('n'));

        switch ($biaya->getPerulangan()) {
            case 'a-harian':
                break;
            case 'b-mingguan':
                break;
            case 'c-bulanan':
                break;
            case 'd-triwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                break;
            case 'e-caturwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                break;
            case 'f-semester':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                break;
            case 'g-tahunan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                break;
        }

        $offset = $jumlah_periode - $periode;

        $pembayaranRutin = $em->getRepository('LanggasSisdikBundle:PembayaranRutin')
            ->findBy([
                'siswa' => $siswa,
                'biayaRutin' => $biaya,
            ], [
                'waktuSimpan' => 'DESC',
            ], self::PERIODE_TAMBAH, $offset)
        ;

        return [
            'biaya' => $biaya,
            'periodePembayaran' => $periode,
            'pembayaranRutin' => $pembayaranRutin,
            'tanggalAwalBayar' => $tanggalAwalBayar,
        ];
    }

    /**
     * Memeriksa apakah seorang siswa memiliki tunggakan pembayaran rutin
     *
     * @param Siswa     $siswa
     * @param \DateTime $tanggalSekarang
     *
     * @return boolean
     */
    private function memilikiTunggakan(Siswa $siswa, \DateTime $tanggalSekarang)
    {
        $em = $this->getDoctrine()->getManager();

        $daftarPerulangan = BiayaRutin::getDaftarPerulangan();
        $bulanSekarang = $tanggalSekarang->format('n');

        if ($siswa->getPenjurusan() instanceof Penjurusan) {
            $daftarBiayaRutin = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                ->orderBy('biaya.perulangan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->setParameter('penjurusan', $siswa->getPenjurusan())
                ->getQuery()
                ->getResult()
            ;
        } else {
            $daftarBiayaRutin = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaRutin', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.penjurusan IS NULL')
                ->orderBy('biaya.perulangan', 'ASC')
                ->setParameter('tahun', $siswa->getTahun())
                ->getQuery()
                ->getResult()
            ;
        }

        $bedaWaktu = $tanggalSekarang->diff($siswa->getPembiayaanSejak());

        foreach ($daftarBiayaRutin as $biaya) {
            $jumlahWajibBayar = 0;

            $jumlahPembayaran = $em->createQueryBuilder()
                ->select('COUNT(DISTINCT pembayaran.id)')
                ->from('LanggasSisdikBundle:PembayaranRutin', 'pembayaran')
                ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
                ->where('pembayaran.siswa = :siswa')
                ->andWhere('pembayaran.biayaRutin = :biayaRutin')
                ->setParameter('siswa', $siswa)
                ->setParameter('biayaRutin', $biaya)
                ->groupBy('pembayaran.id')
                ->having('SUM(transaksi.nominalPembayaran) >= (SUM(DISTINCT pembayaran.nominalBiaya) - (SUM(DISTINCT pembayaran.nominalPotongan) + SUM(DISTINCT pembayaran.persenPotonganDinominalkan)))')
                ->getQuery()
                ->getOneOrNullResult()
            ;

            if (is_null($jumlahPembayaran)) {
                $jumlahPembayaran[1] = 0;
            }

            $tmpHariKe = ($biaya->getBulananHariKe() && $biaya->getBulananHariKe() <= 28) ? $biaya->getBulananHariKe() : '01';
            $tanggalAwalBayar = new \DateTime($siswa->getPembiayaanSejak()->format('Y-m-').$tmpHariKe);

            $bedaBulan = abs($biaya->getBulanAwal() - $siswa->getPembiayaanSejak()->format('n'));

            switch ($biaya->getPerulangan()) {
                case 'a-harian':
                    $jumlahWajibBayar = $bedaWaktu->format("%a");

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'b-mingguan':
                    $jumlahWajibBayar = floor($bedaWaktu->format("%a") / 7);

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'c-bulanan':
                    $jumlahWajibBayar = $bedaWaktu->format("%m");

                    if ($bedaWaktu->format("%d") >= $biaya->getBulananHariKe()) {
                        $jumlahWajibBayar++;
                    }

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'd-triwulan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 3);

                    if ($bedaWaktu->format('%m') % 3 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'e-caturwulan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 4);

                    if ($bedaWaktu->format('%m') % 4 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'f-semester':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    $jumlahWajibBayar = ceil($bedaWaktu->format("%m") / 6);

                    if ($bedaWaktu->format('%m') % 6 == 0) {
                        if ($bedaWaktu->format("%r%d") < 0) {
                            $jumlahWajibBayar++;
                        }
                    }

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
                case 'g-tahunan':
                    if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                        $tanggalAwalBayar->modify("+1 year");
                        $tanggalAwalBayar->modify("-$bedaBulan months");
                    } else {
                        $tanggalAwalBayar->modify("+$bedaBulan months");
                    }

                    $bedaWaktu = $tanggalSekarang->diff($tanggalAwalBayar);

                    if ($bedaWaktu->format("%r%a") < 0) {
                        $jumlahWajibBayar++;
                    }

                    if ($jumlahPembayaran[1] < $jumlahWajibBayar) {
                        return true;
                    }

                    break;
            }
        }

        return false;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.pembayaran.biaya.berulang', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
