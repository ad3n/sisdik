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
class SendMessageMassiveCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('sms:massive')->setDescription('Send massive text messages')
                ->addOption('idjadwalkehadirankepulangan', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $idjadwalkehadirankepulangan = $input->getOption('idjadwalkehadirankepulangan');
        $text = '';
        $date = date('Y-m-d');
        $router = $this->getContainer()->get('router');
        $router->getContext()->setBaseUrl('/_local');

        if ($idjadwalkehadirankepulangan == '') {
            $text = "can not proceed. idjadwalkehadirankepulangan is empty";
            $output->writeln($text);
            return 0;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();

        $activeday = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
                ->findOneBy(
                        array(
                            'tanggal' => new \DateTime($date), 'kbm' => TRUE
                        ));
        if (!$activeday) {
            return 0;
        }

        $jadwalkehadirankepulangan = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')
                ->find($idjadwalkehadirankepulangan);

        $tahun = $jadwalkehadirankepulangan->getTahun();
        $kelas = $jadwalkehadirankepulangan->getKelas();
        $statusKehadiranKepulangan = $jadwalkehadirankepulangan->getStatusKehadiranKepulangan();
        $templatetext = $jadwalkehadirankepulangan->getTemplatesms()->getTeks();
        $sekolah = $jadwalkehadirankepulangan->getStatusKehadiranKepulangan()->getSekolah();

        // find all kehadiransiswa by statuskehadirankepulangan and current date
        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:KehadiranSiswa', 't')
                ->where('t.statusKehadiranKepulangan = :statusKehadiranKepulangan')
                ->andWhere('t.tanggal = :tanggal')->andWhere('t.kelas = :kelas')
                ->setParameter('statusKehadiranKepulangan', $statusKehadiranKepulangan->getId())
                ->setParameter('tanggal', new \DateTime($date))
                ->setParameter('kelas', $kelas->getId());

        $entities = $querybuilder->getQuery()->getResult();

        foreach ($entities as $entity) {
            $siswa = $entity->getSiswa();
            $timestamp = strtotime("{$entity->getTanggal()->format('Y-m-d')} {$entity->getJam()}");

            $text .= "processing-sms-massive";
            $textmessage = $templatetext;

            // format teks
            $textmessage = str_replace("<%nama%>", $siswa->getNamaLengkap(), $textmessage);
            $textmessage = str_replace("<%nis%>", $siswa->getNomorInduk(), $textmessage);
            $textmessage = str_replace("<%hari%>", $this->getDayName($timestamp), $textmessage);
            $textmessage = str_replace("<%tanggal%>", date('d/m/Y', $timestamp), $textmessage);
            $textmessage = str_replace("<%jam%>", date('H:i', $timestamp), $textmessage);
            $textmessage = str_replace("<%keterangan%>", $entity->getKeteranganStatus(),
                    $textmessage);

            $parentnumbers = $siswa->getPonselOrangtuawali();

            if ($parentnumbers != '') {
                $phonenumbers = preg_split("/[\s,]+/", $parentnumbers);

                if (is_array($phonenumbers)) {
                    $idlog = $this
                            ->logSMS($siswa->getNomorInduk(), date('Y-m-d H:i:s', $timestamp));

                    $dlrhost = $router
                            ->generate('studentspresence_update',
                                    array(
                                        'id' => $entity->getId(), 'idlog' => $idlog
                                    ), TRUE);

                    $text .= $dlrhost;
                    $dlrurl = $dlrhost . "/%d/%T";

                    // print $teks . "\n";

                    //                     while( list($key, $val) = each($phonenumbers) ) {

                    //                         // send sms
                    //                         $param = "?username=ihsan&password=ihsan&to=".$val."&text=".urlencode($teks)."&dlr-mask=7&dlr-url=".urlencode($dlrurl);
                    //                         $url = 'http://127.0.0.1:13131/cgi-bin/sendsms'.$param;
                    //                         print $dlrurl . "\n";
                    //                         print $url . "\n";
                    //                         $ch = curl_init();
                    //                         curl_setopt($ch, CURLOPT_URL, $url);
                    //                         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    //                         curl_exec($ch);
                    //                         curl_close($ch);

                    //                     }
                }
            }

            $entity->setSmsMassalTerproses(TRUE);
            $em->persist($entity);
        }
        // $em->flush();

        if ($text != '') {
            $output->writeln($text);
        }
    }

    private function logSms() {
        return 0;
    }

    private function getDayName($timestamp) {
        $day = date('N', $timestamp);

        switch ($day) {
            case 1:
                $dayname = "Senin";
                break;
            case 2:
                $dayname = "Selasa";
                break;
            case 3:
                $dayname = "Rabu";
                break;
            case 4:
                $dayname = "Kamis";
                break;
            case 5:
                $dayname = "Jumat";
                break;
            case 6:
                $dayname = "Sabtu";
                break;
            case 7:
                $dayname = "Minggu";
                break;
        }

        return $dayname;
    }
}
