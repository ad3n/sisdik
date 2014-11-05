<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\ProsesKepulanganSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InisiasiKepulanganCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "inisiasi-kepulangan.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 610;

    protected function configure()
    {
        $this
            ->setName('sisdik:kepulangan:inisiasi')
            ->setDescription('Menginisiasi kepulangan siswa.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pembuatan data awal hari ini')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        if ($input->getOption('paksa')) {
            $jam = '13:00:00';
            $waktuSekarang = new \DateTime(date("Y-m-d $jam"));
            $mingguanHariKe = 0; // 0 = senin
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
                        ->andWhere('jadwal.paramstatusDariJam <= :jam')
                        ->andWhere('jadwal.perulangan = :perulangan')
                        ->andWhere('jadwal.permulaan = :permulaan')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('jam', $jam)
                        ->setParameter('perulangan', $key)
                        ->setParameter('permulaan', true)
                        ->setParameter('aktif', true)
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

                        $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                'kelas' => $jadwal->getKelas(),
                                'tanggal' => $waktuSekarang,
                            ])
                        ;
                        if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                            if ($prosesKepulanganSiswa->isBerhasilInisiasi()) {
                                continue;
                            }
                        }

                        $dariJam = $jadwal->getParamstatusDariJam();

                        if ($input->getOption('paksa')) {
                            $dariJam = $jam;
                        }

                        $waktuJadwal = strtotime(date('Y-m-d') . " $dariJam");
                        $bedaWaktu = $waktuSekarang->getTimestamp() - $waktuJadwal;

                        if ($input->getOption('paksa')) {
                            print "[paksa]: param status dari jam = " . $jadwal->getParamstatusDariJam() . "\n";
                            print "[paksa]: waktu jadwal menjadi = " . date("Y-m-d H:i:s", $waktuJadwal) . "\n";
                            print "[paksa]: waktu sekarang menjadi = " . $waktuSekarang->format("Y-m-d H:i:s") . "\n";
                            print "[paksa]: beda waktu = " . $bedaWaktu . "\n";
                        }

                        if ($bedaWaktu >= 0 && $bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                            $qbKehadiran = $em->createQueryBuilder()
                                ->select('kehadiran')
                                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                                ->where('kehadiran.sekolah = :sekolah')
                                ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
                                ->andWhere('kehadiran.kelas = :kelas')
                                ->andWhere('kehadiran.tanggal = :tanggal')
                                ->andWhere('kehadiran.statusKehadiran = :tepat OR kehadiran.statusKehadiran = :telat')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunAkademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas())
                                ->setParameter('tanggal', $waktuSekarang->format('Y-m-d'))
                                ->setParameter('tepat', 'a-hadir-tepat')
                                ->setParameter('telat', 'b-hadir-telat')
                            ;
                            $entitiesKehadiran = $qbKehadiran->getQuery()->getResult();

                            foreach ($entitiesKehadiran as $kehadiran) {
                                if (!(is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa)) {
                                    continue;
                                }

                                $qbKepulangan = $em->createQueryBuilder()
                                    ->select('kepulangan')
                                    ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                                    ->where('kepulangan.sekolah = :sekolah')
                                    ->andWhere('kepulangan.siswa = :siswa')
                                    ->andWhere('kepulangan.tanggal = :tanggal')
                                    ->setParameter('sekolah', $sekolah)
                                    ->setParameter('siswa', $kehadiran->getSiswa())
                                    ->setParameter('tanggal', $waktuSekarang->format('Y-m-d'))
                                ;
                                $entityKepulangan = $qbKepulangan->getQuery()->getResult();

                                if (count($entityKepulangan) >= 1) {
                                    continue;
                                }

                                $kepulangan = new KepulanganSiswa();
                                $kepulangan->setSekolah($jadwal->getSekolah());
                                $kepulangan->setTahunAkademik($jadwal->getTahunAkademik());
                                $kepulangan->setKelas($jadwal->getKelas());
                                $kepulangan->setSiswa($kehadiran->getSiswa());
                                $kepulangan->setStatusKepulangan($jadwal->getStatusKepulangan());
                                $kepulangan->setPermulaan($jadwal->isPermulaan());
                                $kepulangan->setTervalidasi(false);
                                $kepulangan->setTanggal($waktuSekarang);
                                $kepulangan->setJam($jadwal->getParamstatusDariJam());
                                $kepulangan->setSmsTerproses(false);
                                $kepulangan->setKehadiranSiswa($kehadiran);

                                $em->persist($kepulangan);
                            }

                            if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                                $prosesKepulanganSiswa->setBerhasilInisiasi(true);
                            } else {
                                $prosesKepulanganSiswa = new ProsesKepulanganSiswa();
                                $prosesKepulanganSiswa->setSekolah($jadwal->getSekolah());
                                $prosesKepulanganSiswa->setTahunAkademik($jadwal->getTahunAkademik());
                                $prosesKepulanganSiswa->setKelas($jadwal->getKelas());
                                $prosesKepulanganSiswa->setTanggal($waktuSekarang);
                                $prosesKepulanganSiswa->setBerhasilInisiasi(true);
                            }

                            $em->persist($prosesKepulanganSiswa);
                            $em->flush();
                        }
                    }
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses inisiasi kepulangan sekolah " . $sekolah->getNama() . " telah dan sedang berjalan\n";
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
