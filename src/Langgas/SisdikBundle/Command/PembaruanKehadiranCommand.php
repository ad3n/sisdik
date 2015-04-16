<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Langgas\SisdikBundle\Entity\ProsesLogKehadiran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PembaruanKehadiranCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sisdik:kehadiran:pembaruan')
            ->setDescription('Memperbarui kehadiran siswa.')
            ->addOption('prioritas', null, InputOption::VALUE_REQUIRED, "Prioritas [tinggi|normal|rendah]. Default normal.", "normal")
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pembaruan data kehadiran hari ini')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $waktuSekarang = new \DateTime();
        $perulangan = JadwalKehadiran::getDaftarPerulangan();

        $workload['waktu_sekarang'] = $waktuSekarang;
        $workload['paksa'] = $input->getOption('paksa');

        $semuaSekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->findAll();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }
            $workload['sekolah'] = $sekolah->getId();

            if (!$input->getOption('paksa')) {
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
            }

            $mesinKehadiran = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')
                ->findBy([
                    'sekolah' => $sekolah,
                    'aktif' => true,
                ])
            ;

            foreach ($perulangan as $key => $value) {
                $workload['perulangan'] = $key;

                $logDirectory = $this->getContainer()->get('kernel')->getRootDir()
                    .DIRECTORY_SEPARATOR
                    .'fingerprintlogs'
                    .DIRECTORY_SEPARATOR
                    .$sekolah->getId()
                    .DIRECTORY_SEPARATOR
                    .'log'
                    .DIRECTORY_SEPARATOR
                    .$key
                    .DIRECTORY_SEPARATOR
                    .$waktuSekarang->format('Y-m-d')
                ;
                if (!is_dir($logDirectory)) {
                    continue;
                }

                foreach ($mesinKehadiran as $mesin) {
                    if (!(is_object($mesin) && $mesin instanceof MesinKehadiran)) {
                        continue;
                    }
                    if ($mesin->getAlamatIp() == '') {
                        continue;
                    }

                    $logFiles = [];
                    exec("cd $logDirectory && ls -1t {$mesin->getAlamatIp()}*", $logFiles);

                    $logFile = '';
                    foreach ($logFiles as $logFile) {
                        if ($logFile == '') {
                            continue;
                        }

                        if (!$input->getOption('paksa')) {
                            $prosesLog = $em->createQueryBuilder()
                                ->select('COUNT(prosesLog.id)')
                                ->from('LanggasSisdikBundle:ProsesLogKehadiran', 'prosesLog')
                                ->where('prosesLog.sekolah = :sekolah')
                                ->andWhere('prosesLog.namaFile = :namaFile')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('namaFile', $logFile)
                                ->getQuery()
                                ->getSingleScalarResult()
                            ;
                            if ($prosesLog > 0) {
                                continue;
                            }
                        }

                        $workload['log_file'] = $logDirectory.DIRECTORY_SEPARATOR.$logFile;

                        /* @var $logger Logger */
                        $logger = $this->getContainer()->get('monolog.logger.attendance');

                        $gearman = new \GearmanClient();
                        $gearman->addServer();

                        $jobFunction = "LanggasSisdikBundleWorkerPembaruanKehadiranWorker~pembaruan";

                        $proses = new ProsesLogKehadiran();
                        $proses->setAwalProses($waktuSekarang);
                        $proses->setNamaFile($logFile);
                        $proses->setSekolah($sekolah);
                        $proses->setStatusAntrian('a-masuk-antrian');
                        $proses->setPrioritas($input->getOption('prioritas'));

                        $em->persist($proses);
                        $em->flush();

                        $workload['proses_log'] = $proses->getId();

                        switch ($input->getOption('prioritas')) {
                            case "tinggi":
                                $gearman->doHighBackground($jobFunction, serialize($workload));
                                $logger->addInfo($sekolah->getId().' | '.$sekolah->getNama().' | kehadiran | prioritas-tinggi | '.$workload['log_file']);
                                break;
                            case "normal":
                                $gearman->doBackground($jobFunction, serialize($workload));
                                $logger->addInfo($sekolah->getId().' | '.$sekolah->getNama().' | kehadiran | prioritas-normal | '.$workload['log_file']);
                                break;
                            case "rendah":
                                $gearman->doLowBackground($jobFunction, serialize($workload));
                                $logger->addInfo($sekolah->getId().' | '.$sekolah->getNama().' | kehadiran | prioritas-rendah | '.$workload['log_file']);
                                break;
                        }
                    }
                }
            }
        }
    }
}
