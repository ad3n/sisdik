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

        $idtahun = $entity->getIdtahun();
        $idkelas = $entity->getIdkelas();
        $idstatusKehadiranKepulangan = $entity->getIdstatusKehadiranKepulangan();
        $idsekolah = $entity->getIdtahun()->getIdsekolah();

        $activeday = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
                ->findOneBy(
                        array(
                                'tanggal' => new \DateTime($date), 'kbm' => TRUE,
                                'idsekolah' => $idsekolah->getId()
                        ));
        if (!$activeday) {
            return 0;
        }

        // find all siswa with the selected idtahun and idkelas
        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:SiswaKelas', 't')->where('t.idtahun = :idtahun')
                ->andWhere('t.idkelas = :idkelas')->andWhere('t.aktif = :aktif')
                ->setParameter('idtahun', $idtahun->getId())
                ->setParameter('idkelas', $idkelas->getId())->setParameter('aktif', 1);

        $results = $querybuilder->getQuery()->getResult();

        foreach ($results as $result) {
            $idsiswa = $result->getIdsiswa();

            $exists = $em->getRepository('FastSisdikBundle:KehadiranSiswa')
                    ->findOneBy(
                            array(
                                'idsiswa' => $idsiswa->getId(), 'tanggal' => new \DateTime($date)
                            ));

            if (!$exists) {
                $kehadiransiswa = new KehadiranSiswa();
                $kehadiransiswa->setIdsiswa($idsiswa);
                $kehadiransiswa->setIdkelas($idkelas);
                $kehadiransiswa->setIdstatusKehadiranKepulangan($idstatusKehadiranKepulangan);
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
