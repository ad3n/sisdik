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
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class InisiasiKehadiranCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "inisiasi-kehadiran.lock";
    const LOCK_DIR = "lock";

    protected function configure() {
        $this->setName('sisdik:kehadiran:inisiasi')
                ->setDescription('Periksa dan jalankan inisiasi kehadiran.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        $debug = true;
        if ($debug) {
            $jam = '05:00:00';
            $mingguanHariKe = 0; // 0 = senin
            $bulananHariKe = 1;

            print
                    "debug: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe"
                            . "\n";
        }

        $qbSekolah = $em->createQueryBuilder()->select('sekolah')
                ->from('FastSisdikBundle:Sekolah', 'sekolah');
        $entitiesSekolah = $qbSekolah->getQuery()->getResult();
        foreach ($entitiesSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            $kalenderPendidikan = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
                    ->findOneBy(
                            array(
                                'sekolah' => $sekolah, 'tanggal' => $waktuSekarang, 'kbm' => true,
                            ));
            if (!(is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                foreach ($perulangan as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()->select('jadwal')
                            ->from('FastSisdikBundle:JadwalKehadiran', 'jadwal')
                            ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
                            ->andWhere('jadwal.sekolah = :sekolah')
                            ->andWhere('jadwal.paramstatusDariJam = :jam')
                            ->andWhere('jadwal.perulangan = :perulangan')
                            ->andWhere('jadwal.permulaan = :permulaan')
                            ->andWhere('tahunAkademik.aktif = :aktif')
                            ->setParameter('sekolah', $sekolah->getId())->setParameter('jam', $jam)
                            ->setParameter('perulangan', $key)->setParameter('permulaan', true)
                            ->setParameter('aktif', true);
                    if ($key == 'b-mingguan') {
                        $querybuilder->andWhere('jadwal.mingguanHariKe = :harike')
                                ->setParameter('harike', $mingguanHariKe);
                    } elseif ($key == 'c-bulanan') {
                        $querybuilder->andWhere('jadwal.bulananHariKe = :tanggalke')
                                ->setParameter('tanggalke', $bulananHariKe);
                    }
                    $entities = $querybuilder->getQuery()->getResult();
                    foreach ($entities as $jadwal) {
                        if (!(is_object($jadwal) && $jadwal instanceof JadwalKehadiran)) {
                            continue;
                        }

                        $qbSiswaKelas = $em->createQueryBuilder()->select('siswaKelas')
                                ->from('FastSisdikBundle:SiswaKelas', 'siswaKelas')
                                ->where('siswaKelas.tahunAkademik = :tahunakademik')
                                ->andWhere('siswaKelas.kelas = :kelas')
                                ->setParameter('tahunakademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas());
                        $entitiesSiswaKelas = $qbSiswaKelas->getQuery()->getResult();
                        foreach ($entitiesSiswaKelas as $siswaKelas) {
                            if (!(is_object($siswaKelas) && $siswaKelas instanceof SiswaKelas)) {
                                continue;
                            }

                            $qbKehadiran = $em->createQueryBuilder()->select('kehadiran')
                                    ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')
                                    ->where('kehadiran.sekolah = :sekolah')
                                    ->andWhere('kehadiran.siswa = :siswa')
                                    ->andWhere('kehadiran.tanggal = :tanggal')
                                    ->setParameter('sekolah', $sekolah->getId())
                                    ->setParameter('siswa', $siswaKelas->getSiswa()->getId())
                                    ->setParameter('tanggal', $waktuSekarang->format('Y-m-d'));
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
                            $kehadiran->setJam($jam);
                            $kehadiran->setSmsTerproses(false);

                            $em->persist($kehadiran);
                        }

                        $prosesKehadiranSiswa = $em->getRepository('FastSisdikBundle:ProsesKehadiranSiswa')
                                ->findOneBy(
                                        array(
                                                'sekolah' => $jadwal->getSekolah()->getId(),
                                                'tahunAkademik' => $jadwal->getTahunAkademik()->getId(),
                                                'kelas' => $jadwal->getKelas()->getId(),
                                                'tanggal' => $waktuSekarang,
                                        ));
                        if (is_object($prosesKehadiranSiswa)
                                && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
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

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print
                        "proses inisiasi kehadiran sekolah " . $sekolah->getNama()
                                . " telah dan sedang berjalan\n";
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
    private function isLocked($nomorUrutSekolah) {
        $lockfile = $this->getContainer()->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR . self::LOCK_DIR
                . DIRECTORY_SEPARATOR . $nomorUrutSekolah . '.' . self::LOCK_FILE;

        if (file_exists($lockfile)) {
            $lockingPID = trim(file_get_contents($lockfile));

            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            if (in_array($lockingPID, $pids))
                return true;

            print "Removing stale lock file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid() . "\n");

        return false;
    }
}
