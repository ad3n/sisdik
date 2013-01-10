<?php

namespace Fast\SisdikBundle\Command;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\KepulanganSiswa;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class FingerPrintEndSchoolCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('fp:endschool')->setDescription('Proceed students leaving status')
                ->addOption('idjadwalkehadirankepulangan', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $idjadwalkehadirankepulangan = $input->getOption('idjadwalkehadirankepulangan');
        $text = '';
        $newline = "\r\n";
        $timeout = 5;

        if ($idjadwalkehadirankepulangan == '') {
            $text = "can not proceed. idjadwalkehadirankepulangan is empty";
            $output->writeln($text);
            return 0;
        }

        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        $jadwalkehadirankepulangan = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')
                ->find($idjadwalkehadirankepulangan);

        $idtahun = $jadwalkehadirankepulangan->getIdtahun();
        $idkelas = $jadwalkehadirankepulangan->getIdkelas();
        $idstatusKehadiranKepulangan = $jadwalkehadirankepulangan->getIdstatusKehadiranKepulangan();
        $idsekolah = $jadwalkehadirankepulangan->getIdstatusKehadiranKepulangan()->getIdsekolah();

        $paramstatusDariJam = intval(
                preg_replace("/[\.:]/", '', $jadwalkehadirankepulangan->getParamstatusDariJam(TRUE)));

        $paramstatusHinggaJam = intval(
                preg_replace("/[\.:]/", '', $jadwalkehadirankepulangan->getParamstatusHinggaJam(TRUE)));

        $devices = $em->getRepository('FastSisdikBundle:MesinKehadiran')
                ->findBy(
                        array(
                            'idsekolah' => $idsekolah->getId(), 'aktif' => TRUE
                        ));

        foreach ($devices as $device) {
            if ($device->getAktif()) {
                $buffer = '';

                // $output->writeln("Connecting to {$device->getAlamatIp()}:80 --key={$device->getCommkey()}...");

                try {
                    $connection = fsockopen($device->getAlamatIp(), "80", $errno, $errstr, $timeout);
                    if ($connection) {
                        $soapRequest = "<GetAttLog><ArgComKey xsi:type=\"xsd:integer\">"
                                . $device->getCommkey()
                                . "</ArgComKey><Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg></GetAttLog>";
                
                        fwrite($connection, "POST /iWsService HTTP/1.0" . $newline);
                        fwrite($connection, "Content-Type: text/xml" . $newline);
                        fwrite($connection,
                                "Content-Length: " . strlen($soapRequest) . $newline . $newline);
                        fwrite($connection, $soapRequest . $newline);
                
                        while ($response = fgets($connection, 1024)) {
                            $buffer .= $response;
                        }
                
                        // process log buffer here
                        $buffer = preg_replace("/\s+/", ' ', trim($buffer));
                        preg_match_all("/<([\w]+)[^>]*>.*?<\/\\1>/", $buffer, $matches,
                                PREG_SET_ORDER);
                        $xmlstring = "<?xml version='1.0'?>\n" . $matches[0][0];
                
                        $xmlobject = simplexml_load_string($xmlstring);
                
                        if ($xmlobject) {
                            foreach ($xmlobject->xpath('Row') as $item) {
                
                                $siswa = $em->getRepository('FastSisdikBundle:Siswa')
                                        ->findOneBy(
                                                array(
                                                    'nomorIndukSistem' => $item->PIN
                                                ));
                                if ($siswa) {
                
                                    $kepulangansiswa = $em
                                            ->getRepository('FastSisdikBundle:KepulanganSiswa')
                                            ->findOneBy(
                                                    array(
                                                            'idsiswa' => $siswa->getId(),
                                                            'idkelas' => $idkelas->getId(),
                                                            'tanggal' => new \DateTime(
                                                                    $item->DateTime),
                                                            'jam' => NULL,
                                                    ));
                
                                    if ($kepulangansiswa) {
                
                                        // update if it's not a subject of update before
                                        if ($kepulangansiswa->getIdstatusKehadiranKepulangan()->getId()
                                                !== $idstatusKehadiranKepulangan->getId()) {
                
                                            $jamKepulangan = intval(
                                                    date('His', strtotime($item->DateTime)));
                
                                            // proceed if time is within interval
                                            if ($jamKepulangan >= $paramstatusDariJam
                                                    && $jamKepulangan <= $paramstatusHinggaJam) {
                                                $kepulangansiswa
                                                        ->setJam(
                                                                date('H:i:s',
                                                                        strtotime($item->DateTime)));
                                                $kepulangansiswa
                                                        ->setIdstatusKehadiranKepulangan($idstatusKehadiranKepulangan);
                
                                                $em->persist($kepulangansiswa);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $em->flush();
                    }
                } catch (\ErrorException $e) {
                    $text .= "Koneksi ke mesin fingerprint {$device->getAlamatIp()} gagal dilakukan $e"
                            . $newline;
                    $text .= "Pesan Error: $errstr ($errno)" . $newline;
                }
            }
        }

        // process log buffer test here
        /*
        $bufferfilename = "/home/ihsan/test/kehadiran/2012-11-29";
        if (!$handle = fopen($bufferfilename, 'r')) {
            throw new \Exception("can't open file for reading");
        }
        $buffer = fread($handle, filesize($bufferfilename));
        fclose($handle);

        $buffer = preg_replace("/\s+/", ' ', trim($buffer));
        preg_match_all("/<([\w]+)[^>]*>.*?<\/\\1>/", $buffer, $matches, PREG_SET_ORDER);
        $xmlstring = "<?xml version='1.0'?>\n" . $matches[0][0];

        $xmlobject = simplexml_load_string($xmlstring);

        if ($xmlobject) {
            foreach ($xmlobject->xpath('Row') as $item) {

                $siswa = $em->getRepository('FastSisdikBundle:Siswa')
                        ->findOneBy(
                                array(
                                    'nomorIndukSistem' => $item->PIN
                                ));
                if ($siswa) {

                    $kepulangansiswa = $em->getRepository('FastSisdikBundle:KepulanganSiswa')
                            ->findOneBy(
                                    array(
                                            'idsiswa' => $siswa->getId(),
                                            'idkelas' => $idkelas->getId(),
                                            'tanggal' => new \DateTime($item->DateTime),
                                            'jam' => NULL,
                                    ));

                    if ($kepulangansiswa) {

                        // if ($siswa->getNomorIndukSistem() == '1000027') {
                        //     print 
                        //             $siswa->getNomorIndukSistem() . ':' . $idkelas->getId() . ':'
                        //                     . $item->DateTime . ' -> ' . $kepulangansiswa->getId()
                        //                     . ':' . $idstatusKehadiranKepulangan->getId() . "\n";
                        // }

                        // update if it's not a subject of update before
                        if ($kepulangansiswa->getIdstatusKehadiranKepulangan()->getId()
                                !== $idstatusKehadiranKepulangan->getId()) {

                            $jamKepulangan = intval(date('His', strtotime($item->DateTime)));

                            // $text .= "jamKehadiran=" . $jamKepulangan . ':';
                            // $text .= $siswa->getId() . ':' . $kepulangansiswa->getId() . ':';

                            // if ($siswa->getNomorIndukSistem() == '1000356') {
                            //     print $jamKepulangan . '>=' . $paramstatusDariJam . " -- ";
                            //     print $jamKepulangan . '<=' . $paramstatusHinggaJam . "\n";
                            // }

                            // proceed if time is within interval
                            if ($jamKepulangan >= $paramstatusDariJam
                                    && $jamKepulangan <= $paramstatusHinggaJam) {

                                $text .= "processupdate";

                                $kepulangansiswa->setJam(date('H:i:s', strtotime($item->DateTime)));
                                $kepulangansiswa->setIdstatusKehadiranKepulangan($idstatusKehadiranKepulangan);

                                $em->persist($kepulangansiswa);
                            }
                        }
                    }
                }
            }
        }
        $em->flush();
        */

        if ($text != '') {
            $output->writeln($text);
        }
    }
}
