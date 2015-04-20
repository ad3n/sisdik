<?php

namespace Langgas\SisdikBundle\Worker;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\ProsesKepulanganSiswa;
use Langgas\SisdikBundle\Entity\ProsesLogKepulangan;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Mmoreram\GearmanBundle\Driver\Gearman;
use Symfony\Bridge\Monolog\Logger;

/**
 * @Gearman\Work(
 *     iterations = 1,
 *     description = "Worker pembaruan kepulangan siswa.",
 *     defaultMethod = "doBackground",
 *     service = "pembaruan.kepulangan",
 * )
 * @Service("pembaruan.kepulangan")
 */
class PembaruanKepulanganWorker
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
     * Memperbarui kepulangan siswa.
     *
     * @param \GearmanJob $job
     *
     * @Gearman\Job(
     *     name = "pembaruan",
     *     description = "Memperbarui kepulangan siswa."
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

        /* @var $prosesLog ProsesLogKepulangan */
        $prosesLog = $em->getRepository('LanggasSisdikBundle:ProsesLogKepulangan')->find($data['proses_log']);

        $prosesLog->setStatusAntrian('b-sedang-dikerjakan');
        $em->persist($prosesLog);
        $em->flush();

        $jumlahLogDiproses = 0;

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

        $jadwalKepulangan = $querybuilder->getQuery()->getResult();

        foreach ($jadwalKepulangan as $jadwal) {
            if (!(is_object($jadwal) && $jadwal instanceof JadwalKepulangan)) {
                continue;
            }

            $tanggalJadwalDari = new \DateTime($waktuSekarang->format('Y-m-d')." $dariJam");
            $tanggalJadwalHingga = new \DateTime($waktuSekarang->format('Y-m-d')." $hinggaJam");

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
                ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                ->leftJoin('kepulangan.siswa', 'siswa')
                ->where('kepulangan.sekolah = :sekolah')
                ->andWhere('kepulangan.tanggal = :tanggal')
                ->andWhere('kepulangan.permulaan = :permulaan OR kepulangan.tervalidasi = :tervalidasi')
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

                $logKepulangan = json_decode($buffer, true);

                foreach ($logKepulangan as $item) {
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

                            $em->persist($kepulanganSiswa);
                            $em->flush();

                            $jumlahLogDiproses++;
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

                                $em->persist($kepulanganSiswa);
                                $em->flush();

                                $jumlahLogDiproses++;
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
