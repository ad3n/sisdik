<?php

namespace Langgas\SisdikBundle\Command;

use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\ProsesKepulanganSiswa;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PembaruanKepulanganCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "pembaruan-kepulangan.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 1810;
    const TMP_DIR = "/tmp";

    protected function configure()
    {
        $this
            ->setName('sisdik:kepulangan:pembaruan')
            ->setDescription('Memperbarui kepulangan siswa.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pembaruan data kepulangan hari ini')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $tanggalSekarang = $waktuSekarang->format('Y-m-d');
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        if ($input->getOption('paksa')) {
            $jamDari = '13:00:00';
            $jam = '14:00:00';
            $waktuSekarang = new \DateTime(date("Y-m-d $jam"));
            $mingguanHariKe = 2; // 0 = senin
            $bulananHariKe = 1;

            print "[paksa]: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe\n";
        }

        $semuaSekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->findAll();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if ($input->getOption('paksa')) {
                print "[paksa]: " . $sekolah->getNama() . "\n";
            }

            $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tanggal' => $waktuSekarang,
                    'kbm' => true,
                ])
            ;
            if (!(is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                foreach ($perulangan as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()
                        ->select('jadwal')
                        ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwal')
                        ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
                        ->andWhere('jadwal.sekolah = :sekolah')
                        ->andWhere('jadwal.paramstatusHinggaJam <= :jam')
                        ->andWhere('jadwal.perulangan = :perulangan')
                        ->andWhere('jadwal.permulaan = :permulaan')
                        ->andWhere('jadwal.otomatisTerhubungMesin = :terhubung')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('jam', $jam)
                        ->setParameter('perulangan', $key)
                        ->setParameter('permulaan', false)
                        ->setParameter('aktif', true)
                        ->setParameter('terhubung', true)
                        ->orderBy('jadwal.paramstatusHinggaJam', 'ASC')
                    ;

                    if ($key == 'b-mingguan') {
                        $querybuilder
                            ->andWhere('jadwal.mingguanHariKe = :harike')
                            ->setParameter('harike', $mingguanHariKe)
                        ;
                    } elseif ($key == 'c-bulanan') {
                        $querybuilder
                            ->andWhere('jadwal.bulananHariKe = :tanggalke')
                            ->setParameter('tanggalke', $bulananHariKe)
                        ;
                    }

                    $jadwalKepulangan = $querybuilder->getQuery()->getResult();

                    foreach ($jadwalKepulangan as $jadwal) {
                        if (!(is_object($jadwal) && $jadwal instanceof JadwalKepulangan)) {
                            continue;
                        }

                        $dariJam = $jadwal->getParamstatusDariJam();
                        $hinggaJam = $jadwal->getParamstatusHinggaJam();
                        $tanggalJadwalDari = new \DateTime(date("Y-m-d $dariJam"));
                        $tanggalJadwalHingga = new \DateTime(date("Y-m-d $hinggaJam"));

                        if ($input->getOption('paksa')) {
                            $dariJam = $jamDari;
                            $hinggaJam = $jam;
                            $tanggalJadwalDari = new \DateTime(date("Y-m-d $dariJam"));
                            $tanggalJadwalHingga = new \DateTime(date("Y-m-d $hinggaJam"));
                        }

                        $waktuJadwal = strtotime(date('Y-m-d') . " $hinggaJam");
                        $bedaWaktu = $waktuSekarang->getTimestamp() - $waktuJadwal;

                        if ($input->getOption('paksa')) {
                            print "[paksa]: param status hingga jam = " . $jadwal->getParamstatusHinggaJam() . "\n";
                            print "[paksa]: waktu jadwal menjadi = " . date("Y-m-d H:i:s", $waktuJadwal) . "\n";
                            print "[paksa]: waktu sekarang menjadi = " . $waktuSekarang->format("Y-m-d H:i:s") . "\n";
                            print "[paksa]: beda waktu = " . $bedaWaktu . "\n";
                        }

                        if ($bedaWaktu >= 0 && $bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                            $logDirectory = $this->getContainer()->get('kernel')->getRootDir()
                                . DIRECTORY_SEPARATOR
                                . "fingerprintlogs"
                                . DIRECTORY_SEPARATOR
                                . $sekolah->getId()
                                . DIRECTORY_SEPARATOR
                                . 'log'
                                . DIRECTORY_SEPARATOR
                                . $jadwal->getPerulangan()
                                . DIRECTORY_SEPARATOR
                                . $tanggalSekarang
                                . DIRECTORY_SEPARATOR
                                . 'pulang'
                            ;
                            if (!is_dir($logDirectory)) {
                                continue;
                            }

                            $mesinKehadiran = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')
                                ->findBy([
                                    'sekolah' => $sekolah,
                                    'aktif' => true,
                                ])
                            ;

                            foreach ($mesinKehadiran as $mesin) {
                                if (!(is_object($mesin) && $mesin instanceof MesinKehadiran)) {
                                    continue;
                                }
                                if ($mesin->getAlamatIp() == '') {
                                    continue;
                                }

                                $logFile = system("cd $logDirectory && ls -1 {$mesin->getAlamatIp()}* | tail -1");
                                $sourceFile = $logDirectory . DIRECTORY_SEPARATOR . $logFile;
                                $targetFile = self::TMP_DIR
                                    . DIRECTORY_SEPARATOR
                                    . $sekolah->getId()
                                    . '-sisdik-'
                                    . uniqid(mt_rand(), true)
                                    . $logFile
                                ;

                                if (!@copy($sourceFile, $targetFile)) {
                                    continue;
                                }

                                exec("gunzip --force $targetFile");

                                $buffer = file_get_contents(substr($targetFile, 0, -3));

                                if (strstr($targetFile, 'json') !== false) {
                                    $logKepulangan = json_decode($buffer, true);

                                    foreach ($logKepulangan as $item) {
                                        $logTanggal = new \DateTime($item['datetime']);

                                        if ($input->getOption('paksa')) {
                                            $logTanggal = $waktuSekarang;
                                            print "[paksa]: log tanggal = " . $logTanggal->format('Y-m-d') . "\n";
                                        }

                                        // +60 detik perbedaan
                                        if (!($logTanggal->getTimestamp() >= $tanggalJadwalDari->getTimestamp() && $logTanggal->getTimestamp() <= $tanggalJadwalHingga->getTimestamp() + 60)) {
                                            continue;
                                        }

                                        if ($logTanggal->format('Ymd') != $waktuSekarang->format('Ymd')) {
                                            continue;
                                        }

                                        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                                            ->findOneBy([
                                                'nomorIndukSistem' => $item['id'],
                                            ])
                                        ;

                                        if ($input->getOption('paksa')) {
                                            /* @var $siswa Siswa */
                                            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                                                ->findOneBy([
                                                    'nomorIndukSistem' => '1000186',
                                                ])
                                            ;
                                            print "[paksa]: siswa = " . $siswa->getNomorIndukSistem() . "," . $siswa->getNamaLengkap() . "\n";
                                        }

                                        if (is_object($siswa) && $siswa instanceof Siswa) {
                                            $kepulanganSiswa = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                                                ->findOneBy([
                                                    'sekolah' => $sekolah,
                                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                                    'kelas' => $jadwal->getKelas(),
                                                    'siswa' => $siswa,
                                                    'tanggal' => $waktuSekarang,
                                                    'permulaan' => true,
                                                ])
                                            ;
                                            if (is_object($kepulanganSiswa) && $kepulanganSiswa instanceof KepulanganSiswa) {
                                                $kepulanganSiswa->setPermulaan(false);
                                                $kepulanganSiswa->setStatusKepulangan($jadwal->getStatusKepulangan());
                                                $kepulanganSiswa->setJam($logTanggal->format('H:i:s'));

                                                if ($input->getOption('paksa')) {
                                                    $kepulanganSiswa->setStatusKepulangan('b-hadir-telat');
                                                    print "[paksa]: memaksa menjadi hadir telat\n";
                                                }

                                                $em->persist($kepulanganSiswa);
                                                $em->flush();
                                            } else {
                                                if ($input->getOption('paksa')) {
                                                    print "[paksa]: tidak mengubah data yang telah diperbarui sebelumnya\n";
                                                }
                                            }
                                        }
                                    }

                                    $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                                        ->findOneBy([
                                            'sekolah' => $sekolah,
                                            'tahunAkademik' => $jadwal->getTahunAkademik(),
                                            'kelas' => $jadwal->getKelas(),
                                            'tanggal' => $waktuSekarang,
                                            'berhasilDiperbaruiMesin' => false,
                                        ])
                                    ;

                                    if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                                        $prosesKepulanganSiswa->setBerhasilDiperbaruiMesin(true);
                                        $em->persist($prosesKepulanganSiswa);
                                    }
                                } else {
                                    $buffer = preg_replace("/\s+/", ' ', trim($buffer));
                                    preg_match_all("/<([\w]+)[^>]*>.*?<\/\\1>/", $buffer, $matches, PREG_SET_ORDER);
                                    $xmlstring = "<?xml version='1.0'?>\n" . $matches[0][0];

                                    $xmlobject = simplexml_load_string($xmlstring);

                                    if ($xmlobject) {
                                        foreach ($xmlobject->xpath('Row') as $item) {
                                            $logTanggal = new \DateTime($item->DateTime);

                                            if ($input->getOption('paksa')) {
                                                $logTanggal = $waktuSekarang;
                                                print "[paksa]: log tanggal = " . $logTanggal->format('Y-m-d') . "\n";
                                            }

                                            // +60 detik perbedaan
                                            if (!($logTanggal->getTimestamp() >= $tanggalJadwalDari->getTimestamp() && $logTanggal->getTimestamp() <= $tanggalJadwalHingga->getTimestamp() + 60)) {
                                                continue;
                                            }

                                            if ($logTanggal->format('Ymd') != $waktuSekarang->format('Ymd')) {
                                                continue;
                                            }

                                            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                                                ->findOneBy([
                                                    'nomorIndukSistem' => $item->PIN,
                                                ])
                                            ;

                                            if ($input->getOption('paksa')) {
                                                /* @var $siswa Siswa */
                                                $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                                                    ->findOneBy([
                                                        'nomorIndukSistem' => '1000186',
                                                    ])
                                                ;
                                                print "[paksa]: siswa = " . $siswa->getNomorIndukSistem() . "," . $siswa->getNamaLengkap() . "\n";
                                            }

                                            if (is_object($siswa) && $siswa instanceof Siswa) {
                                                $kepulanganSiswa = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                                                    ->findOneBy([
                                                        'sekolah' => $sekolah,
                                                        'tahunAkademik' => $jadwal->getTahunAkademik(),
                                                        'kelas' => $jadwal->getKelas(),
                                                        'siswa' => $siswa,
                                                        'tanggal' => $waktuSekarang,
                                                        'permulaan' => true,
                                                    ])
                                                ;
                                                if (is_object($kepulanganSiswa) && $kepulanganSiswa instanceof KepulanganSiswa) {
                                                    $kepulanganSiswa->setPermulaan(false);
                                                    $kepulanganSiswa->setStatusKepulangan($jadwal->getStatusKepulangan());
                                                    $kepulanganSiswa->setJam($logTanggal->format('H:i:s'));

                                                    if ($input->getOption('paksa')) {
                                                        $kepulanganSiswa->setStatusKepulangan('b-hadir-telat');
                                                        print "[paksa]: memaksa menjadi hadir telat\n";
                                                    }

                                                    $em->persist($kepulanganSiswa);
                                                    $em->flush();
                                                } else {
                                                    if ($input->getOption('paksa')) {
                                                        print "[paksa]: tidak mengubah data yang telah diperbarui sebelumnya\n";
                                                    }
                                                }
                                            }
                                        }

                                        $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                                            ->findOneBy([
                                                'sekolah' => $sekolah,
                                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                                'kelas' => $jadwal->getKelas(),
                                                'tanggal' => $waktuSekarang,
                                                'berhasilDiperbaruiMesin' => false,
                                            ])
                                        ;

                                        if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                                            $prosesKepulanganSiswa->setBerhasilDiperbaruiMesin(true);
                                            $em->persist($prosesKepulanganSiswa);
                                        }
                                    }
                                }

                                @unlink(substr($targetFile, 0, -3));
                            }

                            $em->flush();
                        }
                    }
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses pembaruan kepulangan sekolah " . $sekolah->getNama() . " telah dan sedang berjalan\n";
            }
        }
    }

    /**
     * Memeriksa apakah proses sebelumnya sedang berjalan
     * yang ditandai dengan kuncian file lock
     *
     * @param  int     $nomorUrutSekolah
     * @return boolean
     */
    private function isLocked($nomorUrutSekolah)
    {
        $lockfile = $this->getContainer()->get('kernel')->getRootDir()
            . DIRECTORY_SEPARATOR
            . self::LOCK_DIR
            . DIRECTORY_SEPARATOR
            . $nomorUrutSekolah
            . '.'
            . self::LOCK_FILE
        ;

        if (file_exists($lockfile)) {
            $lockingPID = trim(file_get_contents($lockfile));

            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            if (in_array($lockingPID, $pids))
                return true;

            print "Removing stale $nomorUrutSekolah." . self::LOCK_FILE . " file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid() . "\n");

        return false;
    }
}
