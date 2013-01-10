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
class FingerPrintAlterLateCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this->setName('fp:alter:late')->setDescription('Update students presence status to late')
                ->addOption('idjadwalkehadirankepulangan', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $idjadwalkehadirankepulangan = $input->getOption('idjadwalkehadirankepulangan');
        $text = '';
        $prioritasPembaruan = 1;
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
        // $text .= "paramStatusDariJam=" . $paramstatusDariJam . $newline;

        $paramstatusHinggaJam = intval(
                preg_replace("/[\.:]/", '', $jadwalkehadirankepulangan->getParamstatusHinggaJam(TRUE)));
        // $text .= "paramStatusHinggaJam=" . $paramstatusHinggaJam . $newline;

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
                
                                    $kehadiransiswa = $em
                                            ->getRepository('FastSisdikBundle:KehadiranSiswa')
                                            ->findOneBy(
                                                    array(
                                                            'idsiswa' => $siswa->getId(),
                                                            'idkelas' => $idkelas->getId(),
                                                            'tanggal' => new \DateTime(
                                                                    $item->DateTime),
                                                    ));
                                    if ($kehadiransiswa) {
                                        // $text .= $kehadiransiswa->getIdstatusKehadiranKepulangan()->getId() . '-' . $idstatusKehadiranKepulangan->getId() . ':';
                
                                        // update if it's not a subject of update before
                                        if ($kehadiransiswa->getIdstatusKehadiranKepulangan()->getId()
                                                !== $idstatusKehadiranKepulangan->getId()) {
                
                                            // update if it has lower priority update
                                            if ($kehadiransiswa->getPrioritasPembaruan()
                                                    < $prioritasPembaruan) {
                
                                                // update if datetime log within interval
                                                $jamKehadiran = intval(
                                                        date('His', strtotime($item->DateTime)));
                
                                                // $text .= "jamKehadiran=" . $jamKehadiran . ':';
                                                // $text .= $siswa->getId() . ':' . $kehadiransiswa->getId() . ':';
                
                                                if ($jamKehadiran >= $paramstatusDariJam
                                                        && $jamKehadiran <= $paramstatusHinggaJam) {
                                                    // $text .= "processupdate";
                                                    $kehadiransiswa
                                                            ->setIdstatusKehadiranKepulangan(
                                                                    $idstatusKehadiranKepulangan);
                                                    $kehadiransiswa
                                                            ->setJam(
                                                                    date('H:i:s',
                                                                            strtotime(
                                                                                    $item->DateTime)));
                                                    $kehadiransiswa
                                                            ->setPrioritasPembaruan(
                                                                    $prioritasPembaruan);
                                                    $em->persist($kehadiransiswa);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $em->flush();
                    }
                } catch (\ErrorException $e) {
                    $text .= "Koneksi ke mesin fingerprint {$device->getAlamatIp()} gagal dilakukan"
                            . $newline;
                    $text .= "Pesan Error: $errstr ($errno) $e" . $newline;
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
        
                    $kehadiransiswa = $em->getRepository('FastSisdikBundle:KehadiranSiswa')
                            ->findOneBy(
                                    array(
                                            'idsiswa' => $siswa->getId(),
                                            'idkelas' => $idkelas->getId(),
                                            'tanggal' => new \DateTime($item->DateTime),
                                    ));
                    
                    if ($kehadiransiswa) {
                        
                        // if ($siswa->getNomorIndukSistem() == '1000356') {
                        //     print
                        //             $siswa->getNomorIndukSistem() . ':' . $idkelas->getId() . ':'
                        //                     . $item->DateTime . ' -> ' . $kehadiransiswa->getId() . ':'
                        //                     . $idstatusKehadiranKepulangan->getId() . ' -> '
                        //                     . $kehadiransiswa->getPrioritasPembaruan() . ':'
                        //                     . $prioritasPembaruan . "\n";
                        // }
                        
                        // update if it's not a subject of update before
                        if ($kehadiransiswa->getIdstatusKehadiranKepulangan()->getId()
                                !== $idstatusKehadiranKepulangan->getId()) {
        
                            // update if it has lower priority update
                            if ($kehadiransiswa->getPrioritasPembaruan() < $prioritasPembaruan) {
        
                                // update if datetime log within interval
                                $jamKehadiran = intval(date('His', strtotime($item->DateTime)));
                                
                                // $text .= "jamKehadiran=" . $jamKehadiran . ':';
                                // $text .= $siswa->getId() . ':' . $kehadiransiswa->getId() . ':';
        
                                // if ($siswa->getNomorIndukSistem() == '1000356') {
                                //     print $jamKehadiran . '>=' . $paramstatusDariJam . " -- ";
                                //     print $jamKehadiran . '<=' . $paramstatusHinggaJam . "\n";
                                // }
        
                                if ($jamKehadiran >= $paramstatusDariJam
                                        && $jamKehadiran <= $paramstatusHinggaJam) {
                                    
                                    // $text .= "processupdate";
                                    
                                    $kehadiransiswa->setIdstatusKehadiranKepulangan($idstatusKehadiranKepulangan);
                                    $kehadiransiswa
                                            ->setJam(
                                                    date('H:i:s', strtotime($item->DateTime)));
                                    $kehadiransiswa->setPrioritasPembaruan($prioritasPembaruan);
                                    $em->persist($kehadiransiswa);
                                }
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
