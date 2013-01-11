<?php

namespace Fast\SisdikBundle\Command;
use Fast\SisdikBundle\Entity\KepulanganSiswa;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class FingerPrintInitEndsCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('fp:initends')
                ->setDescription('Initially register students status when school ends.')
                ->addOption('idjadwalkehadirankepulangan', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $idjadwalkehadirankepulangan = $input->getOption('idjadwalkehadirankepulangan');
        $text = '';
        $date = date('Y-m-d');
        // $date = '2012-12-05';

        if ($idjadwalkehadirankepulangan == '') {
            $text = "can not proceed. idjadwalkehadirankepulangan is empty";
            $output->writeln($text);
            return 0;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')
                ->find($idjadwalkehadirankepulangan);

        $tahun = $entity->getTahun();
        $kelas = $entity->getKelas();
        $statusKehadiranKepulangan = $entity->getStatusKehadiranKepulangan();
        $sekolah = $entity->getTahun()->getSekolah();

        $activeday = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
                ->findOneBy(
                        array(
                                'tanggal' => new \DateTime($date), 'kbm' => TRUE,
                                'sekolah' => $sekolah->getId()
                        ));
        if (!$activeday) {
            return 0;
        }

        // find all siswa with the selected tahun and kelas from kehadiransiswa
        // where is present and late (prioritasPembaruan 1 and 2)
        // see the related symfony2 console commands
        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:KehadiranSiswa', 't')->where('t.kelas = :kelas')
                ->andWhere('t.tanggal = :tanggal')
                ->andWhere('t.prioritasPembaruan = 1 OR t.prioritasPembaruan = 2')
                ->setParameter('kelas', $kelas->getId())->setParameter('tanggal', $date);

        $results = $querybuilder->getQuery()->getResult();

        foreach ($results as $result) {
            $siswa = $result->getSiswa();

            $exists = $em->getRepository('FastSisdikBundle:KepulanganSiswa')
                    ->findOneBy(
                            array(
                                'siswa' => $siswa->getId(), 'tanggal' => new \DateTime($date)
                            ));

            if (!$exists) {
                $kepulangansiswa = new KepulanganSiswa();
                $kepulangansiswa->setSiswa($siswa);
                $kepulangansiswa->setKelas($kelas);
                $kepulangansiswa->setStatusKehadiranKepulangan($statusKehadiranKepulangan);
                $kepulangansiswa->setTanggal(new \DateTime($date));
                $kepulangansiswa->setSmsPulangTerproses(FALSE);

                $em->persist($kepulangansiswa);
            }
        }
        $em->flush();

        if ($text != '') {
            $output->writeln($text);
        }
    }
}
