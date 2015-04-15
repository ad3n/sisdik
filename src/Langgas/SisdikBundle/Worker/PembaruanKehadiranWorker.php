<?php

namespace Langgas\SisdikBundle\Worker;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Langgas\SisdikBundle\Entity\ProsesLogKehadiran;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Mmoreram\GearmanBundle\Driver\Gearman;
use Symfony\Bridge\Monolog\Logger;

/**
 * @Gearman\Work(
 *     iterations = 1,
 *     description = "Worker pembaruan kehadiran siswa.",
 *     defaultMethod = "doBackground",
 *     service = "pembaruan.kehadiran",
 * )
 * @Service("pembaruan.kehadiran")
 */
class PembaruanKehadiranWorker
{
    const TMP_DIR = "/tmp";

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @InjectParams({
     *     "entityManager" = @Inject("doctrine.orm.entity_manager"),
     *     "logger" = @Inject("monolog.logger.attendance")
     * })
     *
     * @param EntityManager $entityManager
     * @param Logger        $logger
     */
    public function __construct(EntityManager $entityManager, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Memperbarui kehadiran siswa.
     *
     * @param \GearmanJob $job
     *
     * @Gearman\Job(
     *     name = "pembaruan",
     *     description = "Memperbarui kehadiran siswa."
     * )
     *
     * @return boolean
     */
    public function pembaruan(\GearmanJob $job)
    {
        $em = $this->entityManager;

        $data = unserialize($job->workload());

        $paksa = $data['paksa'];
        $perulangan = $data['perulangan'];
        $logFile = $data['log_file'];

        /* @var $waktuSekarang \DateTime */
        $waktuSekarang = $data['waktu_sekarang'];
        $jam = $waktuSekarang->format('H:i').':00';
        $mingguanHariKe = $waktuSekarang->format('N');
        $bulananHariKe = $waktuSekarang->format('j');

        /* @var $sekolah Sekolah */
        $sekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($data['sekolah']);

        /* @var $prosesLog ProsesLogKehadiran */
        $prosesLog = $em->getRepository('LanggasSisdikBundle:ProsesLogKehadiran')->find($data['proses_log']);

        $prosesLog->setStatusAntrian('b-sedang-dikerjakan');
        $em->persist($prosesLog);
        $em->flush();

        $jumlahLogDiproses = 0;

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwal')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwal')
            ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
            ->where('jadwal.sekolah = :sekolah')
            ->andWhere('jadwal.paramstatusHinggaJam <= :jam')
            ->andWhere('jadwal.perulangan = :perulangan')
            ->andWhere('jadwal.permulaan = :permulaan')
            ->andWhere('jadwal.otomatisTerhubungMesin = :terhubung')
            ->andWhere('tahunAkademik.aktif = :aktif')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('jam', $jam)
            ->setParameter('perulangan', $perulangan)
            ->setParameter('permulaan', false)
            ->setParameter('aktif', true)
            ->setParameter('terhubung', true)
            ->orderBy('jadwal.paramstatusHinggaJam', 'ASC')
        ;

        if ($perulangan == 'b-mingguan') {
            $querybuilder
                ->andWhere('jadwal.mingguanHariKe = :harike')
                ->setParameter('harike', $mingguanHariKe)
            ;
        } elseif ($perulangan == 'c-bulanan') {
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

            $targetFile = self::TMP_DIR
                .DIRECTORY_SEPARATOR
                .$sekolah->getId()
                .'-sisdik-'
                .uniqid(mt_rand(), true)
                .'.gz'
            ;

            if (!@copy($logFile, $targetFile)) {
                continue;
            }

            $siswaTerbarui = $em->createQueryBuilder()
                ->select('siswa.nomorIndukSistem')
                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                ->leftJoin('kehadiran.siswa', 'siswa')
                ->where('kehadiran.sekolah = :sekolah')
                ->andWhere('kehadiran.tanggal = :tanggal')
                ->andWhere('kehadiran.permulaan = :permulaan OR kehadiran.tervalidasi = :tervalidasi')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tanggal', $waktuSekarang->format('Y-m-d'))
                ->setParameter('permulaan', false)
                ->setParameter('tervalidasi', true)
                ->getQuery()
                ->getArrayResult()
            ;
            $nomorTerproses = '';
            foreach ($siswaTerbarui as $val) {
                $nomorTerproses .= $val['nomorIndukSistem'].'|';
            }
            $nomorTerproses = preg_replace('/\|$/', '', $nomorTerproses);

            exec("gunzip --force $targetFile");
            $extractedFile = substr($targetFile, 0, -3);

            if (strstr($targetFile, 'json') !== false) {
                $buffer = file_get_contents($extractedFile);

                $logKehadiran = json_decode($buffer, true);

                foreach ($logKehadiran as $item) {
                    $logTanggal = new \DateTime($item['datetime']);

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

                    if (is_object($siswa) && $siswa instanceof Siswa) {
                        $kehadiranSiswa = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                'kelas' => $jadwal->getKelas(),
                                'siswa' => $siswa,
                                'tanggal' => $waktuSekarang,
                                'permulaan' => true,
                            ])
                        ;
                        if (is_object($kehadiranSiswa) && $kehadiranSiswa instanceof KehadiranSiswa) {
                            $kehadiranSiswa->setPermulaan(false);
                            $kehadiranSiswa->setStatusKehadiran($jadwal->getStatusKehadiran());
                            $kehadiranSiswa->setJam($logTanggal->format('H:i:s'));

                            $em->persist($kehadiranSiswa);
                            $em->flush();

                            $jumlahLogDiproses++;
                        }
                    }
                }

                $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'tahunAkademik' => $jadwal->getTahunAkademik(),
                        'kelas' => $jadwal->getKelas(),
                        'tanggal' => $waktuSekarang,
                        'berhasilDiperbaruiMesin' => false,
                    ])
                ;
                if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                    $prosesKehadiranSiswa->setBerhasilDiperbaruiMesin(true);
                    $em->persist($prosesKehadiranSiswa);
                    $em->flush();
                }
            } else {
                if ($nomorTerproses != '') {
                    exec("sed -i -E '/$nomorTerproses/d' $extractedFile");
                }

                exec("sed -i -n '/<.*>/,\$p' $extractedFile");

                $buffer = file_get_contents($extractedFile);
                $buffer = preg_replace("/\s+/", ' ', trim($buffer));
                $xmlstring = "<?xml version='1.0'?>\n".$buffer;

                $xmlobject = @simplexml_load_string($xmlstring);

                if ($xmlobject) {
                    foreach ($xmlobject->xpath('Row') as $item) {
                        $logTanggal = new \DateTime($item->DateTime);

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

                        if (is_object($siswa) && $siswa instanceof Siswa) {
                            $kehadiranSiswa = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                                ->findOneBy([
                                    'sekolah' => $sekolah,
                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                    'kelas' => $jadwal->getKelas(),
                                    'siswa' => $siswa,
                                    'tanggal' => $waktuSekarang,
                                    'permulaan' => true,
                                ])
                            ;
                            if (is_object($kehadiranSiswa) && $kehadiranSiswa instanceof KehadiranSiswa) {
                                $kehadiranSiswa->setPermulaan(false);
                                $kehadiranSiswa->setStatusKehadiran($jadwal->getStatusKehadiran());
                                $kehadiranSiswa->setJam($logTanggal->format('H:i:s'));

                                $em->persist($kehadiranSiswa);
                                $em->flush();

                                $jumlahLogDiproses++;
                            }
                        }
                    }

                    $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                        ->findOneBy([
                            'sekolah' => $sekolah,
                            'tahunAkademik' => $jadwal->getTahunAkademik(),
                            'kelas' => $jadwal->getKelas(),
                            'tanggal' => $waktuSekarang,
                            'berhasilDiperbaruiMesin' => false,
                        ])
                    ;
                    if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                        $prosesKehadiranSiswa->setBerhasilDiperbaruiMesin(true);
                        $em->persist($prosesKehadiranSiswa);
                        $em->flush();
                    }
                }
            }

            @unlink($extractedFile);
        }

        $prosesLog->setStatusAntrian('c-selesai');
        $prosesLog->setAkhirProses(new \DateTime());
        $prosesLog->setJumlahLogDiproses($jumlahLogDiproses);

        $em->persist($prosesLog);
        $em->flush();

        return true;
    }
}
