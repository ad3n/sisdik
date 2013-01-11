<?php

namespace Fast\SisdikBundle\Command;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class FingerPrintClearLogCommand extends ContainerAwareCommand
{
    const SCHEDULE_DIR = "/fingerprintlogs/";
    const FILE_EXTENSION = ".fplog";

    protected function configure() {
        $this->setName('fp:clearlog')->setDescription('Clear finger print log on a school');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $text = '';
        $perulangan = array(
            'harian', 'mingguan', 'bulanan'
        );
        $newline = "\r\n";
        $timeout = 5;
        $jeda = '+15 minutes'; // jeda pengambilan dan penghapusan log dari jadwal paling akhir
        $date = date('Y-m-d');
        $hari = date('w');
        $tanggal = date('j');
        $waktusistem = $date . ' ' . date('H:i') . ':00';

        $container = $this->getContainer();
        $directory = $container->get('kernel')->getRootDir() . self::SCHEDULE_DIR;
        $fs = new Filesystem();

        // TODO: get log and save it to file in server

        $em = $container->get('doctrine')->getManager();

        $sekolah = $em->getRepository('FastSisdikBundle:Sekolah')->findAll();

        foreach ($sekolah as $entity) {
            $jam = 0;
            $jamdatabase = '';

            // ambil jadwal terakhir di suatu sekolah pada hari sekarang
            foreach ($perulangan as $value) {
                $dql = "SELECT MAX(t.dariJam) AS jam
                        FROM FastSisdikBundle:JadwalKehadiranKepulangan t
                        WHERE t.perulangan = :perulangan";
                $where = '';
                if ($value == 'mingguan') {
                    $where = ' AND t.mingguanHariKe = :harike';
                } else if ($value == 'bulanan') {
                    $where = ' AND t.bulananHariKe = :tanggalke';
                }
                $dql .= $where != '' ? $where : '';

                $query = $em->createQuery($dql)->setParameter('perulangan', $value);
                if ($value == 'mingguan') {
                    $query->setParameter('harike', $hari);
                } else if ($value == 'bulanan') {
                    $query->setParameter('tanggalke', $tanggal);
                }
                $entities = $query->getResult();
                foreach ($entities as $jadwal) {
                    $jamvalue = intval(preg_replace("/[\.:]/", '', $jadwal['jam']));
                    if ($jamvalue > $jam) {
                        $jam = $jamvalue;
                        $jamdatabase = $jadwal['jam'];
                    }
                }
            }

            if ($jamdatabase != '') {
                $waktueksekusi = date('Y-m-d H:i:s', strtotime($date . ' ' . $jamdatabase . $jeda));
                
                // $waktusistem = '2012-12-07 15:56:00';
                // $waktueksekusi = '2012-12-07 15:56:00';
                // print("$waktusistem -> $waktueksekusi\n");
                if (intval(strtotime($waktusistem)) == intval(strtotime($waktueksekusi))) {
                    // print(strtotime($waktusistem) . "->" . strtotime($waktueksekusi) . "\n");
                    $devices = $em->getRepository('FastSisdikBundle:MesinKehadiran')
                            ->findBy(
                                    array(
                                        'sekolah' => $entity->getId(), 'aktif' => TRUE
                                    ));

                    foreach ($devices as $device) {
                        if ($device->getAktif()) {
                            $buffer = '';

                            // $output->writeln("Connecting to {$entity->getId()}:{$device->getAlamatIp()}:80 --key={$device->getCommkey()}...");

                            // backup log from fingerprint device
                            try {
                                $connection = fsockopen($device->getAlamatIp(), "80", $errno,
                                        $errstr, $timeout);
                                if ($connection) {
                                    $soapRequest = "<GetAttLog><ArgComKey xsi:type=\"xsd:integer\">"
                                            . $device->getCommkey()
                                            . "</ArgComKey><Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg></GetAttLog>";

                                    fwrite($connection, "POST /iWsService HTTP/1.0" . $newline);
                                    fwrite($connection, "Content-Type: text/xml" . $newline);
                                    fwrite($connection,
                                            "Content-Length: " . strlen($soapRequest) . $newline
                                                    . $newline);
                                    fwrite($connection, $soapRequest . $newline);

                                    while ($response = fgets($connection, 1024)) {
                                        $buffer .= $response;
                                    }

                                    if (!$fs->exists($directory . $entity->getId())) {
                                        try {
                                            $fs->mkdir($directory . $entity->getId());
                                        } catch (IOException $e) {
                                            $message = $this->get('translator')
                                                    ->trans('errorinfo.cannot.createdirectory',
                                                            array(
                                                                    '%dirname%' => $directory
                                                                            . $entity->getId()
                                                            ));
                                            throw new \Exception($message);
                                        }
                                    }

                                    // write to file
                                    $filename = $directory . $entity->getId() . '/' . date('Y-m-d')
                                            . self::FILE_EXTENSION;
                                    if (!$handle = fopen($filename, 'w')) {
                                        $message = $this->get('translator')
                                                ->trans('errorinfo.cannot.open',
                                                        array(
                                                            '%filename%' => $filename
                                                        ));
                                        throw new \Exception($message);
                                    }
                                    // print $buffer;
                                    // print $filename . "\n";

                                    if (fwrite($handle, $buffer) === FALSE) {
                                        $message = $this->get('translator')
                                                ->trans('errorinfo.cannot.write',
                                                        array(
                                                            '%filename%' => $filename
                                                        ));
                                        throw new \Exception($message);
                                    }

                                    fclose($handle);
                                }
                            } catch (\ErrorException $e) {
                                $text .= "Koneksi ke mesin fingerprint {$device->getAlamatIp()} gagal dilakukan $e"
                                        . $newline;
                                $text .= "Pesan Error: $errstr ($errno)" . $newline;
                            }

                            // clear log from fingerprint device
                            try {
                                $connection = fsockopen($device->getAlamatIp(), "80", $errno,
                                        $errstr, $timeout);
                                if ($connection) {
                                    $soapRequest = "<ClearData><ArgComKey xsi:type=\"xsd:integer\">"
                                            . $device->getCommkey()
                                            . "</ArgComKey><Arg><Value xsi:type=\"xsd:integer\">3</Value></Arg></ClearData>";

                                    fwrite($connection, "POST /iWsService HTTP/1.0" . $newline);
                                    fwrite($connection, "Content-Type: text/xml" . $newline);
                                    fwrite($connection,
                                            "Content-Length: " . strlen($soapRequest) . $newline
                                                    . $newline);
                                    fwrite($connection, $soapRequest . $newline);
                                }
                            } catch (\ErrorException $e) {
                                $text .= "Koneksi ke mesin fingerprint {$device->getAlamatIp()} gagal dilakukan"
                                        . $newline;
                                $text .= "Pesan Error: $errstr ($errno) $e" . $newline;
                            }
                        }
                    }
                }
            }
        }

        if ($text != '') {
            $output->writeln($text);
        }
    }
}
