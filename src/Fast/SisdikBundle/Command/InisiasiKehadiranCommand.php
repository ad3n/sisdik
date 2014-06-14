<?php
namespace Fast\SisdikBundle\Command;

use Fast\SisdikBundle\Entity\KalenderPendidikan;
use Fast\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InisiasiKehadiranCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "inisiasi-kehadiran.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 610;

    protected function configure()
    {
        $this
            ->setName('sisdik:kehadiran:inisiasi')
            ->setDescription('Periksa dan jalankan inisiasi kehadiran.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pembuatan data awal hari ini')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        if ($input->getOption('paksa')) {
            $jam = '05:00:00';
            $waktuSekarang = new \DateTime(date("Y-m-d $jam"));
            $mingguanHariKe = 0; // 0 = senin
            $bulananHariKe = 1;

            print "[paksa]: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe\n";
        }

        $qbSekolah = $em->createQueryBuilder()
            ->select('sekolah')
            ->from('FastSisdikBundle:Sekolah', 'sekolah')
        ;
        $semuaSekolah = $qbSekolah->getQuery()->getResult();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if ($input->getOption('paksa')) {
                print "[paksa]: " . $sekolah->getNama() . "\n";
            }

            $kalenderPendidikan = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
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
                        ->from('FastSisdikBundle:JadwalKehadiran', 'jadwal')
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

                    $jadwalKehadiran = $querybuilder->getQuery()->getResult();

                    foreach ($jadwalKehadiran as $jadwal) {
                        if (!(is_object($jadwal) && $jadwal instanceof JadwalKehadiran)) {
                            continue;
                        }

                        $prosesKehadiranSiswa = $em->getRepository('FastSisdikBundle:ProsesKehadiranSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                'kelas' => $jadwal->getKelas(),
                                'tanggal' => $waktuSekarang,
                            ])
                        ;
                        if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                            if ($prosesKehadiranSiswa->isBerhasilInisiasi()) {
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
                            $qbSiswaKelas = $em->createQueryBuilder()
                                ->select('siswaKelas')
                                ->from('FastSisdikBundle:SiswaKelas', 'siswaKelas')
                                ->where('siswaKelas.tahunAkademik = :tahunakademik')
                                ->andWhere('siswaKelas.kelas = :kelas')
                                ->setParameter('tahunakademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas())
                            ;
                            $entitiesSiswaKelas = $qbSiswaKelas->getQuery()->getResult();

                            foreach ($entitiesSiswaKelas as $siswaKelas) {
                                if (!(is_object($siswaKelas) && $siswaKelas instanceof SiswaKelas)) {
                                    continue;
                                }

                                $qbKehadiran = $em->createQueryBuilder()
                                    ->select('kehadiran')
                                    ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')
                                    ->where('kehadiran.sekolah = :sekolah')
                                    ->andWhere('kehadiran.siswa = :siswa')
                                    ->andWhere('kehadiran.tanggal = :tanggal')
                                    ->setParameter('sekolah', $sekolah->getId())
                                    ->setParameter('siswa', $siswaKelas->getSiswa()->getId())
                                    ->setParameter('tanggal', $waktuSekarang->format('Y-m-d'))
                                ;
                                $entityKehadiran = $qbKehadiran->getQuery()->getResult();

                                if (count($entityKehadiran) >= 1) {
                                    continue;
                                }

                                $kehadiran = new KehadiranSiswa();
                                $kehadiran->setSekolah($jadwal->getSekolah());
                                $kehadiran->setTahunAkademik($jadwal->getTahunAkademik());
                                $kehadiran->setKelas($jadwal->getKelas());
                                $kehadiran->setSiswa($siswaKelas->getSiswa());
                                $kehadiran->setStatusKehadiran($jadwal->getStatusKehadiran());
                                $kehadiran->setPermulaan($jadwal->isPermulaan());
                                $kehadiran->setTanggal($waktuSekarang);
                                $kehadiran->setJam($jadwal->getParamstatusDariJam());
                                $kehadiran->setSmsTerproses(false);

                                $em->persist($kehadiran);
                            }

                            if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                                $prosesKehadiranSiswa->setBerhasilInisiasi(true);
                            } else {
                                $prosesKehadiranSiswa = new ProsesKehadiranSiswa();
                                $prosesKehadiranSiswa->setSekolah($jadwal->getSekolah());
                                $prosesKehadiranSiswa->setTahunAkademik($jadwal->getTahunAkademik());
                                $prosesKehadiranSiswa->setKelas($jadwal->getKelas());
                                $prosesKehadiranSiswa->setTanggal($waktuSekarang);
                                $prosesKehadiranSiswa->setBerhasilInisiasi(true);
                            }

                            $em->persist($prosesKehadiranSiswa);
                            $em->flush();
                        }
                    }
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses inisiasi kehadiran sekolah " . $sekolah->getNama() . " telah dan sedang berjalan\n";
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

            print "Removing stale $nomorUrutSekolah.inisiasi-kehadiran.lock file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid() . "\n");

        return false;
    }
}
