<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\LayananSmsPeriodik;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Tingkat;
use Langgas\SisdikBundle\Entity\Templatesms;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\ProsesSmsPeriodik;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PengirimanPesanPeriodikCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "pengiriman-pesan-periodik.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 3650;

    protected function configure()
    {
        $this
            ->setName('sisdik:layanansms:periodik')
            ->setDescription('Mengirim pesan periodik.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pengiriman pesan periodik')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug perintah pengiriman pesan periodik')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smsTerproses = 0;

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $text = '';
        $perulangan = LayananSmsPeriodik::getDaftarPerulangan();
        $daftarLayananPeriodik = PilihanLayananSms::getDaftarLayananPeriodik();
        $waktuSekarang = new \DateTime();
        $mingguanHariKe = $waktuSekarang->format('N');
        $bulananHariKe = $waktuSekarang->format('j');
        $bulanSekarang = $waktuSekarang->format('n');

        $semuaSekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->findAll();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                    ])
                ;
                if (!$vendorSekolah instanceof VendorSekolah) {
                    continue;
                }

                $pilihanLayananSms = $em->createQueryBuilder()
                    ->select('pilihanLayananSms')
                    ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
                    ->where('pilihanLayananSms.sekolah = :sekolah')
                    ->andWhere('pilihanLayananSms.jenisLayanan IN (?1)')
                    ->andWhere('pilihanLayananSms.status = :status')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter(1, array_keys($daftarLayananPeriodik))
                    ->setParameter('status', true)
                    ->getQuery()
                    ->getResult()
                ;
                if (count($pilihanLayananSms) <= 0) {
                    continue;
                }

                /* @var $pilihanLayanan PilihanLayananSms */
                foreach ($pilihanLayananSms as $pilihanLayanan) {
                    $layananSmsPeriodik = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')
                        ->findBy([
                            'sekolah' => $sekolah,
                            'jenisLayanan' => $pilihanLayanan->getJenisLayanan(),
                            'aktif' => true,
                        ])
                    ;
                    if (count($layananSmsPeriodik) <= 0) {
                        continue;
                    }

                    /* @var $layananSms LayananSmsPeriodik */
                    foreach ($layananSmsPeriodik as $layananSms) {
                        $bulanAwal = $layananSms->getBulanAwal();

                        $timestampWaktuJadwal = strtotime(date('Y-m-d')." ".$layananSms->getSmsJam());
                        $bedaWaktu = abs($waktuSekarang->getTimestamp() - $timestampWaktuJadwal);

                        if ($input->getOption('paksa')) {
                            $bedaWaktu = 0;
                            $mingguanHariKe = 7;
                            print "[paksa]: Memaksa beda waktu menjadi 0 dan hari menjadi 7 (minggu)\n";
                        }

                        switch ($layananSms->getPerulangan()) {
                            case 'a-harian':
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'b-mingguan';
                                if ($layananSms->getMingguanHariKe() != $mingguanHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'c-bulanan';
                                if ($layananSms->getBulananHariKe() != $bulananHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'd-triwulan';
                                if ($bulanAwal != $bulanSekarang || $bulanAwal + 3 != $bulanSekarang || $bulanAwal + 6 != $bulanSekarang || $bulanAwal + 9 != $bulanSekarang) {
                                    continue;
                                }
                                if ($layananSms->getBulananHariKe() != $bulananHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'e-caturwulan';
                                if ($bulanAwal != $bulanSekarang || $bulanAwal + 4 != $bulanSekarang || $bulanAwal + 8 != $bulanSekarang) {
                                    continue;
                                }
                                if ($layananSms->getBulananHariKe() != $bulananHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'f-semester';
                                if ($bulanAwal != $bulanSekarang || $bulanAwal + 6 != $bulanSekarang) {
                                    continue;
                                }
                                if ($layananSms->getBulananHariKe() != $bulananHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                            case 'g-tahunan';
                                if ($bulanAwal != $bulanSekarang) {
                                    continue;
                                }
                                if ($layananSms->getBulananHariKe() != $bulananHariKe) {
                                    continue;
                                }
                                if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                                    $smsTerproses = $this->kirimSms($input, $sekolah, $vendorSekolah, $layananSms, $waktuSekarang);
                                }

                                break;
                        }
                    }
                }

                if ($input->getOption('debug')) {
                    $text .= "[debug]: SMS periodik terproses = $smsTerproses";
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses pengiriman pesan periodik sekolah ".$sekolah->getNama()." telah dan sedang berjalan\n";
            }
        }
    }

    /**
     * @param InputInterface     $input
     * @param Sekolah            $sekolah
     * @param VendorSekolah      $vendorSekolah
     * @param LayananSmsPeriodik $layananSms
     * @param \DateTime          $waktuSekarang
     */
    private function kirimSms(
        InputInterface $input,
        Sekolah $sekolah,
        VendorSekolah $vendorSekolah,
        LayananSmsPeriodik $layananSms,
        \DateTime $waktuSekarang
    ) {
        if (!$layananSms->getTemplatesms() instanceof Templatesms) {
            return 0;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        $prosesSmsPeriodik = $em->getRepository('LanggasSisdikBundle:ProsesSmsPeriodik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'layananSmsPeriodik' => $layananSms,
                'tanggal' => $waktuSekarang,
            ])
        ;
        if ($prosesSmsPeriodik instanceof ProsesSmsPeriodik) {
            if ($prosesSmsPeriodik->getBerhasilKirimSms() == true) {
                return 0;
            }
        }

        $smsKehadiran = 0;
        $daftarBulan = LayananSmsPeriodik::getDaftarNamaBulan();
        $daftarHari = LayananSmsPeriodik::getDaftarNamaHariSingkat();
        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();

        /* @var $translator Translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale("id_ID");

        switch ($layananSms->getJenisLayanan()) {
            case 'zca-kehadiran-kepulangan-rekap-mingguan':
                $terkirim = false;
                $siswaHadir = [];

                $waktuSekarang->modify("-6 days");
                $dariTanggal = $waktuSekarang->format('Y-m-d');
                $dariTanggalFormatIndonesia = $waktuSekarang->format('d/m/Y');
                $waktuSekarang->modify("+6 days");

                $qbSiswaHadir = $em->createQueryBuilder()
                    ->select('DISTINCT(siswa.id)')
                    ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
                    ->leftJoin('kehadiranSiswa.siswa', 'siswa')
                    ->where('kehadiranSiswa.sekolah = :sekolah')
                    ->andWhere('kehadiranSiswa.tanggal BETWEEN :dariTanggal AND :hinggaTanggal')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('dariTanggal', $dariTanggal)
                    ->setParameter('hinggaTanggal', $waktuSekarang->format('Y-m-d'))
                ;

                if ($layananSms->getTingkat() instanceof Tingkat) {
                    $qbSiswaHadir
                        ->leftJoin('kehadiranSiswa.kelas', 'kelas')
                        ->andWhere('kelas.tingkat = :tingkat')
                        ->setParameter('tingkat', $layananSms->getTingkat())
                    ;
                }

                $dataSiswa = array_map('current', $qbSiswaHadir->getQuery()->getResult());

                if (count($dataSiswa) > 0) {
                    foreach ($dataSiswa as $value) {
                        if (!in_array($value, $siswaHadir)) {
                            $siswaHadir[] = $value;
                        }
                    }
                }

                if (count($siswaHadir) > 0) {
                    foreach ($siswaHadir as $idsiswa) {
                        $teksRekapitulasi = $layananSms->getTemplatesms()->getTeks();
                        $teksHadirPulang = '';

                        $qbKehadiranSiswa = $em->createQueryBuilder()
                            ->select('kehadiranSiswa')
                            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
                            ->leftJoin('kehadiranSiswa.siswa', 'siswa')
                            ->where('kehadiranSiswa.sekolah = :sekolah')
                            ->andWhere('kehadiranSiswa.tanggal BETWEEN :dariTanggal AND :hinggaTanggal')
                            ->andWhere('kehadiranSiswa.siswa = :siswa')
                            ->setParameter('sekolah', $sekolah)
                            ->setParameter('dariTanggal', $dariTanggal)
                            ->setParameter('hinggaTanggal', $waktuSekarang->format('Y-m-d'))
                            ->setParameter('siswa', $idsiswa)
                            ->orderBy('kehadiranSiswa.tanggal', 'ASC')
                        ;

                        $kehadiranSiswa = $qbKehadiranSiswa->getQuery()->getResult();

                        /* @var $kehadiran KehadiranSiswa */
                        foreach ($kehadiranSiswa as $kehadiran) {
                            $teksRekapitulasi = str_replace("%nama%", $kehadiran->getSiswa()->getNamaLengkap(), $teksRekapitulasi);
                            $teksRekapitulasi = str_replace("%nis%", $kehadiran->getSiswa()->getNomorInduk(), $teksRekapitulasi);
                            $teksRekapitulasi = str_replace("%kelas%", $kehadiran->getKelas()->getNama(), $teksRekapitulasi);
                            $teksRekapitulasi = str_replace("%dari%", $dariTanggalFormatIndonesia, $teksRekapitulasi);
                            $teksRekapitulasi = str_replace("%hingga%", $waktuSekarang->format('d/m/Y'), $teksRekapitulasi);

                            $stringHari = $translator->trans($daftarHari[$kehadiran->getTanggal()->format('N')]);
                            $stringStatusKehadiran = str_replace(' ', '',/** @Ignore */ $translator->trans($daftarStatusKehadiran[$kehadiran->getStatusKehadiran()]));

                            if ($kehadiran->getStatusKehadiran() == 'a-hadir-tepat' || $kehadiran->getStatusKehadiran() == 'b-hadir-telat') {
                                $stringJamDatang = '***';
                                if ($kehadiran->getJam(false) != '') {
                                    $stringJamDatang = $kehadiran->getJam(false);
                                }

                                $kepulangan = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                                    ->findOneBy([
                                        'kehadiranSiswa' => $kehadiran,
                                    ])
                                ;
                                $stringJamPulang = '***';
                                if ($kepulangan instanceof KepulanganSiswa) {
                                    if ($kehadiran->getJam(false) != '') {
                                        $stringJamPulang = $kepulangan->getJam(false);
                                    } else {
                                        $stringJamPulang = 'PTT';
                                    }
                                }

                                $teksHadirPulang .= $stringHari.':'.$stringStatusKehadiran.' '.$stringJamDatang.'-'.$stringJamPulang.', ';
                            } else {
                                $teksHadirPulang .= $stringHari.':'.$stringStatusKehadiran.', ';
                            }
                        }

                        $teksHadirPulang = preg_replace('/, $/', '', $teksHadirPulang);
                        $teksRekapitulasi = str_replace("%rekap-kehadiran-kepulangan%", $teksHadirPulang, $teksRekapitulasi);

                        if ($input->getOption('debug')) {
                            print "[debug]: ".$teksRekapitulasi."\n";
                        }

                        $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                            ->findOneBy([
                                'siswa' => $idsiswa,
                                'aktif' => true,
                            ])
                        ;
                        if ($ortuWaliAktif instanceof OrangtuaWali) {
                            $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                            if ($ponselOrtuWaliAktif != "") {
                                $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWaliAktif);
                                foreach ($nomorponsel as $ponsel) {
                                    $messenger = $this->getContainer()->get('sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
                                        if ($vendorSekolah->getJenis() == 'khusus') {
                                            $messenger->setUseVendor(true);
                                            $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                        }
                                        $messenger->setPhoneNumber($ponsel);
                                        $messenger->setMessage($teksRekapitulasi);

                                        if ($input->getOption('debug')) {
                                            $messenger->populateMessage();
                                            print "[debug]: ".$messenger->getMessageCommand()."\n\n";
                                        } else {
                                            $messenger->sendMessage($sekolah);
                                        }

                                        $smsKehadiran++;

                                        $terkirim = true;
                                    }
                                }
                            }
                        }
                    }

                    if ($terkirim === true) {
                        if ($prosesSmsPeriodik instanceof ProsesSmsPeriodik) {
                            $prosesSmsPeriodik->setBerhasilKirimSms(true);
                        } else {
                            $prosesSmsPeriodik = new ProsesSmsPeriodik();
                            $prosesSmsPeriodik->setSekolah($sekolah);
                            $prosesSmsPeriodik->setLayananSmsPeriodik($layananSms);
                            $prosesSmsPeriodik->setTanggal($waktuSekarang);
                            $prosesSmsPeriodik->setBerhasilKirimSms(true);
                        }

                        if (!$input->getOption('debug')) {
                            $em->persist($prosesSmsPeriodik);
                            $em->flush();
                        }
                    }
                }

                break;
        }

        return $smsKehadiran;
    }

    /**
     * Memeriksa apakah proses sebelumnya sedang berjalan
     * yang ditandai dengan terkunci lock file
     *
     * @param  int     $nomorUrutSekolah
     * @return boolean
     */
    private function isLocked($nomorUrutSekolah)
    {
        $lockfile = $this->getContainer()->get('kernel')->getRootDir()
            .DIRECTORY_SEPARATOR
            .self::LOCK_DIR
            .DIRECTORY_SEPARATOR
            .$nomorUrutSekolah
            .'.'
            .self::LOCK_FILE
        ;

        if (file_exists($lockfile)) {
            $lockingPID = trim(file_get_contents($lockfile));

            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            if (in_array($lockingPID, $pids)) {
                return true;
            }

            print "Removing stale $nomorUrutSekolah.".self::LOCK_FILE." file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid()."\n");

        return false;
    }
}
