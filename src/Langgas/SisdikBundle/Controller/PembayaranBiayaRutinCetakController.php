<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\PembayaranRutin;
use Langgas\SisdikBundle\Entity\PilihanCetakKwitansi;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranRutin;
use Langgas\SisdikBundle\Util\EscapeCommand;
use PHPPdf\Core\Facade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/cetak-pembayaran-biaya-berulang/{sid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PembayaranBiayaRutinCetakController extends Controller
{
    const RECEIPTS_DIR = "/receipts/";

    /**
     * Cetak nota pembayaran biaya berulang
     *
     * @Route("/{pid}/{tid}", name="pembayaran_biaya_rutin__cetaknota")
     */
    public function printReceiptAction($sid, $pid, $tid)
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $pembayaran = $em->getRepository('LanggasSisdikBundle:PembayaranRutin')->find($pid);
        if (!(is_object($pembayaran) && $pembayaran instanceof PembayaranRutin)) {
            throw $this->createNotFoundException('Entity PembayaranRutin tak ditemukan.');
        }

        $biaya = $pembayaran->getBiayaRutin();

        $transaksi = $em->getRepository('LanggasSisdikBundle:TransaksiPembayaranRutin')->find($tid);
        if (!$transaksi && !($transaksi instanceof TransaksiPembayaranRutin)) {
            throw $this->createNotFoundException('Entity TransaksiPembayaranRutin tak ditemukan.');
        }

        $transaksiPembayaran = $em->getRepository('LanggasSisdikBundle:TransaksiPembayaranRutin')
            ->findBy([
                'pembayaranRutin' => $pid,
            ], [
                'waktuSimpan' => 'ASC',
            ])
        ;

        $namaBiaya = $pembayaran->getNamaBiaya();
        $nominalBiaya = $pembayaran->getNominalBiaya();
        $nominalPotongan = $pembayaran->getNominalPotongan() + $pembayaran->getPersenPotonganDinominalkan();
        $hargaItem = $nominalBiaya - $nominalPotongan;

        $counterTransaksi = 0;
        $nomorCicilan = 1;
        $nomorTransaksi = [];
        foreach ($transaksiPembayaran as $t) {
            if ($t instanceof TransaksiPembayaranRutin) {
                $counterTransaksi++;
                $nomorTransaksi[$t->getNomorTransaksi()] = $t->getNomorTransaksi();
                if ($t->getId() == $tid) {
                    $nomorCicilan = $counterTransaksi;
                    break;
                }
            }
        }

        $nomorCicilan = count($transaksiPembayaran) <= 1 ? 1 : $nomorCicilan;

        $totalPembayaranHinggaTransaksiTerpilih = $pembayaran->getTotalNominalTransaksiPembayaranRutinHinggaTransaksiTerpilih($nomorTransaksi);

        $adaCicilan = false;
        if (count($pembayaran->getTransaksiPembayaranRutin()) > 1) {
            $adaCicilan = true;
        } elseif (count($pembayaran->getTransaksiPembayaranRutin()) == 1) {
            if ($hargaItem > $pembayaran->getTotalNominalTransaksiPembayaranRutin()) {
                $adaCicilan = true;
            }
        }

        $pembayaranBiaya = $em->createQueryBuilder()
            ->select('pembayaran')
            ->from('LanggasSisdikBundle:PembayaranRutin', 'pembayaran')
            ->leftJoin('pembayaran.transaksiPembayaranRutin', 'transaksi')
            ->where('pembayaran.siswa = :siswa')
            ->andWhere('pembayaran.biayaRutin = :biayaRutin')
            ->setParameter('siswa', $siswa)
            ->setParameter('biayaRutin', $biaya)
            ->getQuery()
            ->getResult()
        ;

        $counter = 0;
        foreach ($pembayaranBiaya as $p) {
            if ($p instanceof PembayaranRutin) {
                $counter++;
                if ($p->getId() == $pembayaran->getId()) {
                    $periodePembayaran = $counter;
                }
            }
        }

        $jumlahPeriode = count($pembayaranBiaya);

        $tmpHariKe = ($biaya->getBulananHariKe() && $biaya->getBulananHariKe() <= 28) ? $biaya->getBulananHariKe() : '01';
        $tanggalAwalBayar = new \DateTime($siswa->getPembiayaanSejak()->format('Y-m-').$tmpHariKe);

        $bedaBulan = abs($biaya->getBulanAwal() - $siswa->getPembiayaanSejak()->format('n'));

        $tempoPeriode = $periodePembayaran - 1;

        switch ($biaya->getPerulangan()) {
            case 'a-harian':
                $tanggalAwalBayar->modify('+'.$tempoPeriode.' days');

                break;
            case 'b-mingguan':
                $tanggalAwalBayar->modify('+'.$tempoPeriode.' weeks');

                break;
            case 'c-bulanan':
                $tanggalAwalBayar->modify('+'.$tempoPeriode.' months');

                break;
            case 'd-triwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $tanggalAwalBayar->modify('+'.(($tempoPeriode - 1) * 3).' months');

                break;
            case 'e-caturwulan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $tanggalAwalBayar->modify('+'.(($tempoPeriode - 1) * 4).' months');

                break;
            case 'f-semester':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $tanggalAwalBayar->modify('+'.(($tempoPeriode - 1) * 6).' months');

                break;
            case 'g-tahunan':
                if ($biaya->getBulanAwal() < $siswa->getPembiayaanSejak()->format('n')) {
                    $tanggalAwalBayar->modify("+1 year");
                    $tanggalAwalBayar->modify("-$bedaBulan months");
                } else {
                    $tanggalAwalBayar->modify("+$bedaBulan months");
                }

                $tanggalAwalBayar->modify('+'.$tempoPeriode.' years');

                break;
        }

        $tahun = $transaksi->getWaktuSimpan()->format('Y');
        $bulan = $transaksi->getWaktuSimpan()->format('m');

        /* @var $translator Translator */
        $translator = $this->get('translator');
        $formatter = new \NumberFormatter($this->container->getParameter('locale'), \NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        $output = 'pdf';
        $pilihanCetak = $em->getRepository('LanggasSisdikBundle:PilihanCetakKwitansi')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;
        if ($pilihanCetak instanceof PilihanCetakKwitansi) {
            $output = $pilihanCetak->getOutput();
        }

        $fs = new Filesystem();
        $schoolReceiptDir = $this->get('kernel')->getRootDir().self::RECEIPTS_DIR.$sekolah->getId();
        if (!$fs->exists($schoolReceiptDir.DIRECTORY_SEPARATOR.$tahun.DIRECTORY_SEPARATOR.$bulan)) {
            $fs->mkdir($schoolReceiptDir.DIRECTORY_SEPARATOR.$tahun.DIRECTORY_SEPARATOR.$bulan);
        }

        if ($output == 'esc_p') {
            $filetarget = $transaksi->getNomorTransaksi().".sisdik.direct";
            $documenttarget = $schoolReceiptDir.DIRECTORY_SEPARATOR.$tahun.DIRECTORY_SEPARATOR.$bulan.DIRECTORY_SEPARATOR.$filetarget;

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

            $commands->addContent($sekolah->getNama()."\r\n");
            $commands->addContent($sekolah->getAlamat().", ".$sekolah->getKodepos()."\r\n");

            $phonefaxline = $sekolah->getTelepon() != "" ? $translator->trans('telephone', [], 'printing')." ".$sekolah->getTelepon() : "";
            $phonefaxline .=
                $sekolah->getFax() != "" ?
                    (
                        $phonefaxline != "" ?
                            ", ".$translator->trans('faximile', [], 'printing')." ".$sekolah->getFax()
                            : $translator->trans('faximile', [], 'printing')." ".$sekolah->getFax()
                    )
                    : ""
            ;

            $commands->addContent($phonefaxline."\r\n");

            $commands->addContent(str_repeat("=", $maxwidth)."\r\n");
            $commands->addContent("\r\n");

            $nomorkwitansi = $translator->trans('receiptnum', [], 'printing');
            $spasi = str_repeat(" ", ($labelwidth2 - strlen($nomorkwitansi)));
            $barisNomorkwitansi = $nomorkwitansi.$spasi.": ".$transaksi->getNomorTransaksi();

            $namasiswa = $translator->trans('nama.siswa', [], 'printing');
            $spasi = str_repeat(" ", ($labelwidth1 - strlen($namasiswa)));
            $barisNamasiswa = $namasiswa.$spasi.": ".$siswa->getNamaLengkap();

            $tanggal = $translator->trans('date', [], 'printing');
            $spasi = str_repeat(" ", ($labelwidth2 - strlen($tanggal)));
            $dateFormatter = $this->get('bcc_extra_tools.date_formatter');
            $barisTanggal = $tanggal.$spasi.": ".$dateFormatter->format($transaksi->getWaktuSimpan(), 'long');

            $nomorIdentitas = $translator->trans('identitas.sisdik', [], 'printing').' / '.$translator->trans('nomor.induk', [], 'printing');
            $spasi = str_repeat(" ", ($labelwidth1 - strlen($nomorIdentitas)));
            $barisNomorIdentitas = $nomorIdentitas.$spasi.": ".$siswa->getNomorIndukSistem().' / '.$siswa->getNomorInduk();

            $pengisiBaris1 = strlen($barisNomorkwitansi);
            $pengisiBaris2 = strlen($barisTanggal);
            $pengisiBarisTerbesar = $pengisiBaris1 > $pengisiBaris2 ? $pengisiBaris1 : $pengisiBaris2;

            $sisaBaris1 = $maxwidth2 - strlen($barisNamasiswa) - $pengisiBarisTerbesar;
            $sisaBaris2 = $maxwidth2 - strlen($barisNomorIdentitas) - $pengisiBarisTerbesar;

            $commands->addContent(str_repeat(" ", $marginBadan).$barisNamasiswa.str_repeat(" ", $sisaBaris1).$barisNomorkwitansi."\r\n");
            $commands->addContent(str_repeat(" ", $marginBadan).$barisNomorIdentitas.str_repeat(" ", $sisaBaris2).$barisTanggal."\r\n");
            $commands->addContent("\r\n");

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
            $d = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filetarget);
            $response->headers->set('Content-Disposition', $d);
            $response->headers->set('Content-Description', 'Dokumen kwitansi pembayaran biaya berulang');
            $response->headers->set('Content-Type', 'application/vnd.sisdik.directprint');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', filesize($documenttarget));
        } else {
            $filetarget = $transaksi->getNomorTransaksi().".sisdik.pdf";
            $documenttarget = $schoolReceiptDir.DIRECTORY_SEPARATOR.$tahun.DIRECTORY_SEPARATOR.$bulan.DIRECTORY_SEPARATOR.$filetarget;

            $namaItemPembayaranCicilan = $pembayaran->getNamaBiaya();

            /* @var $facade Facade */
            $facade = $this->get('ps_pdf.facade');
            $tmpResponse = new Response();

            $this
                ->render('LanggasSisdikBundle:PembayaranBiayaRutin:receipts.pdf.twig', [
                    'sekolah' => $sekolah,
                    'siswa' => $siswa,
                    'pembayaran' => $pembayaran,
                    'transaksi' => $transaksi,
                    'namaBiaya' => $namaBiaya,
                    'nominalBiaya' => $nominalBiaya,
                    'adaCicilan' => $adaCicilan,
                    'nomorCicilan' => $nomorCicilan,
                    'totalPembayaranHinggaTransaksiTerpilih' => $totalPembayaranHinggaTransaksiTerpilih,
                    'jumlahPeriode' => $jumlahPeriode,
                    'periodePembayaran' => $periodePembayaran,
                    'tanggalAwalBayar' => $tanggalAwalBayar,
                ], $tmpResponse)
            ;
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
            $response->headers->set('Content-Description', 'Dokumen kwitansi biaya berulang');
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', filesize($documenttarget));
        }

        return $response;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
