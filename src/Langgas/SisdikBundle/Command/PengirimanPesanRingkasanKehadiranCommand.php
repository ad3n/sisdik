<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PengirimanPesanRingkasanKehadiranCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "pengiriman-pesan-ringkasan-kehadiran.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 310;

    protected function configure()
    {
        $this
            ->setName('sisdik:kehadiran:pesanringkasan')
            ->setDescription('Mengirim pesan ringkasan kehadiran siswa di suatu kelas.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pengiriman pesan ringkasan kehadiran')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug perintah pengiriman pesan ringkasan kehadiran')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smsTerproses = 0;

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale("id_ID");

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $waktuSekarang = new \DateTime();
        $mingguanHariKe = $waktuSekarang->format('N');
        $bulananHariKe = $waktuSekarang->format('j');

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

                $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'zza-ringkasan-kehadiran',
                        'status' => true,
                    ])
                ;
                if (!$layananSms instanceof PilihanLayananSms) {
                    continue;
                }

                $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'aktif' => true,
                    ])
                ;
                if (!$tahunAkademikAktif instanceof TahunAkademik) {
                    continue;
                }

                $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'tanggal' => $waktuSekarang,
                        'kbm' => true,
                    ])
                ;
                if (!$kalenderPendidikan instanceof KalenderPendidikan) {
                    continue;
                }

                $waliKelasAktif = $em->createQueryBuilder()
                    ->select('waliKelas')
                    ->from('LanggasSisdikBundle:WaliKelas', 'waliKelas')
                    ->leftJoin('waliKelas.user', 'user')
                    ->where('waliKelas.tahunAkademik = :tahunAkademik')
                    ->andWhere('waliKelas.kirimIkhtisarKehadiran = :kirimIkhtisar')
                    ->andWhere('waliKelas.templatesmsIkhtisarKehadiran IS NOT NULL')
                    ->andWhere("user.nomorPonsel <> ''")
                    ->setParameter('tahunAkademik', $tahunAkademikAktif)
                    ->setParameter('kirimIkhtisar', true)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->getResult()
                ;

                /* @var $waliKelas WaliKelas */
                foreach ($waliKelasAktif as $waliKelas) {
                    /* @var $jadwalKehadiranTerawal JadwalKehadiran */
                    $jadwalKehadiranTerawal = null;
                    $jamTerawal = 0;

                    foreach ($perulangan as $key => $value) {
                        $qbJadwalKehadiran = $em->createQueryBuilder()
                            ->select('jadwal')
                            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwal')
                            ->where('jadwal.sekolah = :sekolah')
                            ->andWhere('jadwal.tahunAkademik = :tahunAkademik')
                            ->andWhere('jadwal.kelas = :kelas')
                            ->andWhere('jadwal.perulangan = :perulangan')
                            ->andWhere('jadwal.smsJam IS NOT NULL')
                            ->andWhere("jadwal.smsJam <> ''")
                            ->setParameter('sekolah', $sekolah)
                            ->setParameter('tahunAkademik', $tahunAkademikAktif)
                            ->setParameter('kelas', $waliKelas->getKelas())
                            ->setParameter('perulangan', $key)
                            ->addOrderBy('jadwal.smsJam', 'ASC')
                        ;

                        if ($key == 'b-mingguan') {
                            $qbJadwalKehadiran
                                ->andWhere('jadwal.mingguanHariKe = :harike')
                                ->setParameter('harike', $mingguanHariKe)
                            ;
                        } elseif ($key == 'c-bulanan') {
                            $qbJadwalKehadiran
                                ->andWhere('jadwal.bulananHariKe = :tanggalke')
                                ->setParameter('tanggalke', $bulananHariKe)
                            ;
                        }

                        $jadwalKehadiran = $qbJadwalKehadiran->getQuery()->useQueryCache(true)->getResult();

                        if (count($jadwalKehadiran) > 0) {
                            /* @var $jadwal JadwalKehadiran */
                            foreach ($jadwalKehadiran as $jadwal) {
                                $jamSms = intval(str_replace(':', '', $jadwal->getSmsJam()));
                                if ($jamTerawal == 0 || $jamTerawal > $jamSms) {
                                    $jamTerawal = $jamSms;
                                    $jadwalKehadiranTerawal = $jadwal;
                                }
                            }
                        }
                    }

                    if ($jamTerawal != 0 || $jadwalKehadiranTerawal instanceof JadwalKehadiran) {
                        $timestampWaktuJadwal = strtotime(date('Y-m-d')." ".$jadwalKehadiranTerawal->getSmsJam());
                        $bedaWaktu = abs($waktuSekarang->getTimestamp() - $timestampWaktuJadwal);

                        $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $tahunAkademikAktif,
                                'kelas' => $waliKelas->getKelas(),
                                'tanggal' => $waktuSekarang,
                                'berhasilKirimSmsRingkasan' => false,
                            ])
                        ;
                        if (!$prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                            continue;
                        }

                        if ($input->getOption('paksa')) {
                            $bedaWaktu = 0;
                        }

                        if ($input->getOption('debug')) {
                            print $jadwalKehadiranTerawal->getSmsJam()."\n";
                        }

                        if ($bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                            $kehadiranSiswa = $em->createQueryBuilder()
                                ->select('kehadiran')
                                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                                ->where('kehadiran.sekolah = :sekolah')
                                ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                                ->andWhere('kehadiran.kelas = :kelas')
                                ->andWhere('kehadiran.tanggal = :tanggal')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunakademik', $tahunAkademikAktif)
                                ->setParameter('kelas', $waliKelas->getKelas())
                                ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                                ->getQuery()
                                ->useQueryCache(true)
                                ->getResult()
                            ;

                            if (count($kehadiranSiswa) <= 0) {
                                continue;
                            }

                            $jumlahTepat = 0;
                            $jumlahTelat = 0;
                            $jumlahAlpa = 0;
                            $jumlahIzin = 0;
                            $jumlahSakit = 0;

                            /* @var $kehadiran KehadiranSiswa */
                            foreach ($kehadiranSiswa as $kehadiran) {
                                switch ($kehadiran->getStatusKehadiran()) {
                                    case 'a-hadir-tepat':
                                        $jumlahTepat++;
                                        break;
                                    case 'b-hadir-telat':
                                        $jumlahTelat++;
                                        break;
                                    case 'c-alpa':
                                        $jumlahAlpa++;
                                        break;
                                    case 'd-izin':
                                        $jumlahIzin++;
                                        break;
                                    case 'e-sakit':
                                        $jumlahSakit++;
                                        break;
                                }
                            }

                            $teksRingkasan = $waliKelas->getTemplatesmsIkhtisarKehadiran()->getTeks();

                            $teksRingkasan = str_replace("%nama%", $waliKelas->getUser()->getName(), $teksRingkasan);
                            $teksRingkasan = str_replace("%kelas%", $waliKelas->getKelas()->getNama(), $teksRingkasan);

                            $indeksHari = $waktuSekarang->format('N');
                            $teksRingkasan = str_replace("%hari%",/** @Ignore */ $translator->trans($namaNamaHari[$indeksHari]), $teksRingkasan);

                            $teksRingkasan = str_replace("%tanggal%", $waktuSekarang->format('d/m/Y'), $teksRingkasan);
                            $teksRingkasan = str_replace("%jumlah-tepat%", $jumlahTepat, $teksRingkasan);
                            $teksRingkasan = str_replace("%jumlah-telat%", $jumlahTelat, $teksRingkasan);
                            $teksRingkasan = str_replace("%jumlah-alpa%", $jumlahAlpa, $teksRingkasan);
                            $teksRingkasan = str_replace("%jumlah-sakit%", $jumlahIzin, $teksRingkasan);
                            $teksRingkasan = str_replace("%jumlah-izin%", $jumlahSakit, $teksRingkasan);

                            $terkirim = false;
                            $nomorponsel = preg_split("/[\s,\/]+/", $waliKelas->getUser()->getNomorPonsel());
                            foreach ($nomorponsel as $ponsel) {
                                $messenger = $this->getContainer()->get('sisdik.messenger');
                                if ($messenger instanceof Messenger) {
                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                        $messenger->setUseVendor(true);
                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                    }
                                    $messenger->setPhoneNumber($ponsel);
                                    $messenger->setMessage($teksRingkasan);

                                    if ($input->getOption('debug')) {
                                        $messenger->populateMessage();
                                        print "[debug]: ".$messenger->getMessageCommand()."\n";
                                    } else {
                                        $messenger->sendMessage($sekolah);
                                    }

                                    $smsTerproses++;
                                    $terkirim = true;
                                }
                            }

                            if ($terkirim) {
                                if ($prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                                    if (!$input->getOption('debug')) {
                                        $prosesKehadiranSiswa->setBerhasilKirimSmsRingkasan(true);
                                        $em->persist($prosesKehadiranSiswa);
                                    }
                                }
                            }

                            if (!$input->getOption('debug')) {
                                $em->flush();
                            }
                        }
                    }
                }

                if ($input->getOption('debug')) {
                    $text .= "[debug]: SMS ringkasan kehadiran terproses = $smsTerproses";
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses pengiriman pesan ringkasan kehadiran sekolah ".$sekolah->getNama()." telah dan sedang berjalan\n";
            }
        }
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
