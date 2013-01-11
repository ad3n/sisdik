<?php

namespace Fast\SisdikBundle\Command;
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
class FingerPrintInitPresenceCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('fp:initpresence')
                ->setDescription('Initially register students presence status.')
                ->addOption('idjadwalkehadirankepulangan', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $idjadwalkehadirankepulangan = $input->getOption('idjadwalkehadirankepulangan');
        $text = '';
        $date = date('Y-m-d');
        // $date = '2012-12-07';

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

        // find all siswa with the selected tahun and kelas
        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:SiswaKelas', 't')->where('t.tahun = :tahun')
                ->andWhere('t.kelas = :kelas')->andWhere('t.aktif = :aktif')
                ->setParameter('tahun', $tahun->getId())
                ->setParameter('kelas', $kelas->getId())->setParameter('aktif', 1);

        $results = $querybuilder->getQuery()->getResult();

        foreach ($results as $result) {
            $siswa = $result->getSiswa();

            $exists = $em->getRepository('FastSisdikBundle:KehadiranSiswa')
                    ->findOneBy(
                            array(
                                'siswa' => $siswa->getId(), 'tanggal' => new \DateTime($date)
                            ));

            if (!$exists) {
                $kehadiransiswa = new KehadiranSiswa();
                $kehadiransiswa->setSiswa($siswa);
                $kehadiransiswa->setKelas($kelas);
                $kehadiransiswa->setStatusKehadiranKepulangan($statusKehadiranKepulangan);
                $kehadiransiswa->setTanggal(new \DateTime($date));
                $kehadiransiswa->setPrioritasPembaruan(0);
                $kehadiransiswa->setSmsTerproses(FALSE);

                $em->persist($kehadiransiswa);
            }
        }
        $em->flush();

        if ($text != '') {
            $output->writeln($text);
        }
    }
}
