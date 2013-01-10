<?php

namespace Fast\SisdikBundle\Command;
use Fast\SisdikBundle\Entity\JadwalKehadiranKepulangan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class SisdikSchedulesCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('sisdik:schedules')
                ->setDescription('Check and run sisdik schedules entries.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $text = '';
        $perulangan = array(
            'harian', 'mingguan', 'bulanan'
        );
        $jam = date('H:i') . ':00';
        $hari = date('w');
        $tanggal = date('j');
        // $jam = '07:00:00';
        // $hari = 2;
        // $tanggal = 1;
        $logcommand = TRUE;
        $commands = "\n------" . date('Y-m-d') . " $jam------\n";

        /*
        // create lock file?
         */

        $em = $this->getContainer()->get('doctrine')->getManager();

        foreach ($perulangan as $value) {
            // jadwal kehadiran/kepulangan
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')
                    ->where(
                            '(:jam BETWEEN t.dariJam AND t.hinggaJam)
                    OR t.dariJam = :jam')->andWhere('t.perulangan = :perulangan')
                    ->setParameter('jam', $jam)->setParameter('perulangan', $value);
            if ($value == 'mingguan') {
                $querybuilder->andWhere('t.mingguanHariKe = :harike')
                        ->setParameter('harike', $hari);
            } else if ($value == 'bulanan') {
                $querybuilder->andWhere('t.bulananHariKe = :tanggalke')
                        ->setParameter('tanggalke', $tanggal);
            }
            $entities = $querybuilder->getQuery()->getResult();
            foreach ($entities as $entity) {
                $commands .= "sf2 {$entity->getCommandJadwal()} --idjadwalkehadirankepulangan={$entity
                        ->getId()} $value\n";
                $fpcommand = $this->getApplication()->find($entity->getCommandJadwal());
                $arguments = array(
                        'command' => $entity->getCommandJadwal(),
                        '--idjadwalkehadirankepulangan' => $entity->getId(), '--env' => 'prod'
                );
                $input = new ArrayInput($arguments);
                $returnCode = $fpcommand->run($input, $output);
            }

            // jadwal sms realtime
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')
                    ->where('(:jam BETWEEN t.smsRealtimeDariJam AND t.smsRealtimeHinggaJam)')
                    ->andWhere('t.kirimSmsRealtime = :kirimsmsrealtime')
                    ->andWhere('t.perulangan = :perulangan')->setParameter('jam', $jam)
                    ->setParameter('kirimsmsrealtime', 1)->setParameter('perulangan', $value);
            if ($value == 'mingguan') {
                $querybuilder->andWhere('t.mingguanHariKe = :harike')
                        ->setParameter('harike', $hari);
            } else if ($value == 'bulanan') {
                $querybuilder->andWhere('t.bulananHariKe = :tanggalke')
                        ->setParameter('tanggalke', $tanggal);
            }
            $entities = $querybuilder->getQuery()->getResult();
            foreach ($entities as $entity) {
                // $commands .= "sf2 {$entity->getCommandJadwal()} --idjadwalkehadirankepulangan={$entity->getId()}\n";
                $fpcommand = $this->getApplication()->find($entity->getCommandJadwal());
                $arguments = array(
                        'command' => $entity->getCommandJadwal(),
                        '--idjadwalkehadirankepulangan' => $entity->getId(), '--env' => 'prod'
                );
                $input = new ArrayInput($arguments);
                // $returnCode = $fpcommand->run($input, $output);
            }

            // jadwal sms massal
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->where('t.smsMassalJam = :jam')
                    ->andWhere('t.kirimSmsMassal = :kirimsmsmassal')
                    ->andWhere('t.perulangan = :perulangan')->setParameter('jam', $jam)
                    ->setParameter('kirimsmsmassal', 1)->setParameter('perulangan', $value);
            if ($value == 'mingguan') {
                $querybuilder->andWhere('t.mingguanHariKe = :harike')
                        ->setParameter('harike', $hari);
            } else if ($value == 'bulanan') {
                $querybuilder->andWhere('t.bulananHariKe = :tanggalke')
                        ->setParameter('tanggalke', $tanggal);
            }
            $entities = $querybuilder->getQuery()->getResult();
            foreach ($entities as $entity) {
                // $commands .= "sf2 {$entity->getCommandJadwal()} --idjadwalkehadirankepulangan={$entity->getId()}\n";
                $fpcommand = $this->getApplication()->find($entity->getCommandJadwal());
                $arguments = array(
                        'command' => $entity->getCommandJadwal(),
                        '--idjadwalkehadirankepulangan' => $entity->getId(), '--env' => 'prod'
                );
                $input = new ArrayInput($arguments);
                // $returnCode = $fpcommand->run($input, $output);
            }
        }

        /*
        // release lock file?
         */

        if ($logcommand) {
            if (!$handle = fopen("/tmp/commands", 'w')) {
                throw new \Exception("can't open file for writing");
            }
            if (fwrite($handle, $commands) === FALSE) {
                throw new \Exception("can't write to file");
            }
            fclose($handle);
        }

        if ($text != '') {
            $output->writeln($text);
        }
    }

    private function fingerPrintCommandDaily() {

    }
}
