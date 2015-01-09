<?php

namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\ProsesKepulanganSiswa;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PengirimanPesanKepulanganCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "pengiriman-pesan-kepulangan.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 610;

    protected function configure()
    {
        $this
            ->setName('sisdik:kepulangan:pesan')
            ->setDescription('Mengirim pesan kepulangan siswa.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pengiriman pesan kepulangan untuk hari ini')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Menampilkan informasi debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smsKepulanganTerproses = 0;

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale("id_ID");

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $waktuSekarang = new \DateTime();
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('N');
        $bulananHariKe = $waktuSekarang->format('j');

        if ($input->getOption('paksa')) {
            $jam = '09:00:00';
            $waktuSekarang = new \DateTime(date("Y-m-d $jam"));
            $mingguanHariKe = 1; // 1 = senin
            $bulananHariKe = 1;

            print "[paksa]: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe\n";
        }

        $semuaSekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->findAll();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                    ])
                ;

                $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'aktif' => true,
                    ])
                ;

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

                $qbKepulanganSemua = $em->createQueryBuilder()
                    ->select('COUNT(kepulangan.id)')
                    ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                    ->where('kepulangan.sekolah = :sekolah')
                    ->andWhere('kepulangan.tahunAkademik = :tahunAkademik')
                    ->andWhere('kepulangan.tanggal = :tanggal')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunAkademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                ;
                $jumlahKepulanganSemua = $qbKepulanganSemua->getQuery()->getSingleScalarResult();

                $qbKepulanganPermulaan = $em->createQueryBuilder()
                    ->select('COUNT(kepulangan.id)')
                    ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                    ->where('kepulangan.sekolah = :sekolah')
                    ->andWhere('kepulangan.tahunAkademik = :tahunAkademik')
                    ->andWhere('kepulangan.tanggal = :tanggal')
                    ->andWhere('kepulangan.permulaan = :permulaan')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunAkademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                    ->setParameter('permulaan', true)
                ;
                $jumlahKepulanganPermulaan = $qbKepulanganPermulaan->getQuery()->getSingleScalarResult();

                // jangan kirim pesan jika semua data kepulangan hari ini masih berstatus permulaan
                if ($jumlahKepulanganSemua == $jumlahKepulanganPermulaan) {
                    continue;
                }

                foreach ($perulangan as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()
                        ->select('jadwal')
                        ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwal')
                        ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
                        ->where('jadwal.sekolah = :sekolah')
                        ->andWhere('jadwal.smsJam <= :jam')
                        ->andWhere('jadwal.perulangan = :perulangan')
                        ->andWhere('jadwal.kirimSms = :kirimsms')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('jam', $jam)
                        ->setParameter('perulangan', $key)
                        ->setParameter('kirimsms', true)
                        ->setParameter('aktif', true)
                    ;

                    if ($key == 'b-mingguan') {
                        $querybuilder
                            ->andWhere('jadwal.mingguanHariKe = :harike')
                            ->setParameter('harike', $mingguanHariKe)
                        ;
                    } elseif ($key == 'c-bulanan') {
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

                        $jenisLayananSms = 'tak-terdefinisi';
                        switch ($jadwal->getStatusKepulangan()) {
                            case 'a-pulang-tercatat':
                                $jenisLayananSms = 'u-kepulangan-tercatat';
                                break;
                            case 'b-pulang-tak-tercatat':
                                $jenisLayananSms = 'v-kepulangan-tak-tercatat';
                                break;
                        }

                        $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => $jenisLayananSms,
                                'status' => true,
                            ])
                        ;
                        if (!(is_object($layananSms) && $layananSms instanceof PilihanLayananSms)) {
                            continue;
                        }

                        $smsJam = $jadwal->getSmsJam();

                        if ($input->getOption('paksa')) {
                            $smsJam = $jam;
                        }

                        $waktuJadwal = strtotime(date('Y-m-d') . " $smsJam");
                        $bedaWaktu = $waktuSekarang->getTimestamp() - $waktuJadwal;

                        if ($input->getOption('paksa')) {
                            print "[paksa]: kirim sms jam = " . $jadwal->getSmsJam() . "\n";
                            print "[paksa]: waktu jadwal menjadi = " . date("Y-m-d H:i:s", $waktuJadwal) . "\n";
                            print "[paksa]: waktu sekarang menjadi = " . $waktuSekarang->format("Y-m-d H:i:s") . "\n";
                            print "[paksa]: beda waktu = " . $bedaWaktu . "\n";
                        }

                        if ($bedaWaktu >= 0 && $bedaWaktu <= self::BEDA_WAKTU_MAKS) {
                            $qbKepulangan = $em->createQueryBuilder()
                                ->select('kepulangan')
                                ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                                ->where('kepulangan.sekolah = :sekolah')
                                ->andWhere('kepulangan.tahunAkademik = :tahunakademik')
                                ->andWhere('kepulangan.kelas = :kelas')
                                ->andWhere('kepulangan.tanggal = :tanggal')
                                ->andWhere('kepulangan.statusKepulangan = :statuskepulangan')
                                ->andWhere('kepulangan.smsTerproses = :terproses')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunakademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas())
                                ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                                ->setParameter('statuskepulangan', $jadwal->getStatusKepulangan())
                                ->setParameter('terproses', false)
                            ;
                            $entitiesKepulangan = $qbKepulangan->getQuery()->getResult();

                            foreach ($entitiesKepulangan as $kepulangan) {
                                if (!(is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa)) {
                                    continue;
                                }

                                $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                                    ->findOneBy([
                                        'siswa' => $kepulangan->getSiswa(),
                                        'aktif' => true,
                                    ])
                                ;

                                if ((is_object($ortuWaliAktif) && $ortuWaliAktif instanceof OrangtuaWali)) {
                                    $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                                    if ($ponselOrtuWaliAktif != "") {
                                        $tekstemplate = $jadwal->getTemplatesms()->getTeks();
                                        $tekstemplate = str_replace("%nama%", $kepulangan->getSiswa()->getNamaLengkap(), $tekstemplate);
                                        $tekstemplate = str_replace("%nis%", $kepulangan->getSiswa()->getNomorInduk(), $tekstemplate);

                                        $indeksHari = $kepulangan->getTanggal()->format('N');
                                        $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$indeksHari]), $tekstemplate);

                                        $tekstemplate = str_replace("%tanggal%", $kepulangan->getTanggal()->format('d/m/Y'), $tekstemplate);

                                        if ($kepulangan->isTervalidasi() && (str_replace(':', '', $kepulangan->getJam()) <= str_replace(':', '', $jadwal->getParamstatusDariJam()))) {
                                            $tekstemplate = str_replace("%jam%", "", $tekstemplate);
                                        } else {
                                            $tekstemplate = str_replace("%jam%", $kepulangan->getJam(), $tekstemplate);
                                        }

                                        $tekstemplate = str_replace("%keterangan%", $kepulangan->getKeteranganStatus(), $tekstemplate);

                                        $terkirim = false;
                                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWaliAktif);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->getContainer()->get('sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                if ($vendorSekolah instanceof VendorSekolah) {
                                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                                        $messenger->setUseVendor(true);
                                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                                    }
                                                }
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);

                                                if ($input->getOption('debug')) {
                                                    $messenger->populateMessage();
                                                    print "[debug]: " . $messenger->getMessageCommand() . "\n";
                                                } else {
                                                    $messenger->sendMessage($sekolah);
                                                }

                                                $smsKepulanganTerproses++;

                                                if ($input->getOption('paksa')) {
                                                    print $smsKepulanganTerproses . ", ";
                                                }

                                                $terkirim = true;
                                            }
                                        }

                                        if ($terkirim) {
                                            if (!$input->getOption('debug')) {
                                                $kepulangan->setSmsTerproses($terkirim);
                                                $em->persist($kepulangan);
                                            }
                                        }
                                    }
                                }
                            }

                            $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                                ->findOneBy([
                                    'sekolah' => $sekolah,
                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                    'kelas' => $jadwal->getKelas(),
                                    'tanggal' => $waktuSekarang,
                                    'berhasilKirimSms' => false,
                                ])
                            ;

                            if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                                if (!$input->getOption('debug')) {
                                    $prosesKepulanganSiswa->setBerhasilKirimSms(true);
                                    $em->persist($prosesKepulanganSiswa);
                                }
                            }

                            if (!$input->getOption('debug')) {
                                $em->flush();
                            }
                        }
                    }
                }

                if ($input->getOption('debug')) {
                    $text .= "[debug]: SMS kepulangan terproses = $smsKepulanganTerproses";
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses pengiriman pesan kepulangan sekolah " . $sekolah->getNama() . " telah dan sedang berjalan\n";
            }
        }
    }

    /**
     * Memeriksa apakah proses sebelumnya sedang berjalan
     * yang ditandai dengan terkunci lock file
     *
     * @param  int     $nomorUrutSekolah
     * @return boolean
     */
    private function isLocked($nomorUrutSekolah)
    {
        $lockfile = $this->getContainer()->get('kernel')->getRootDir()
            . DIRECTORY_SEPARATOR
            . self::LOCK_DIR
            . DIRECTORY_SEPARATOR
            . $nomorUrutSekolah
            . '.'
            . self::LOCK_FILE
        ;

        if (file_exists($lockfile)) {
            $lockingPID = trim(file_get_contents($lockfile));

            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            if (in_array($lockingPID, $pids))
                return true;

            print "Removing stale $nomorUrutSekolah." . self::LOCK_FILE . " file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid() . "\n");

        return false;
    }
}
