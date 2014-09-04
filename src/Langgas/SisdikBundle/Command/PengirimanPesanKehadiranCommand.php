<?php
namespace Langgas\SisdikBundle\Command;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PengirimanPesanKehadiranCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "pengiriman-pesan-kehadiran.lock";
    const LOCK_DIR = "lock";
    const BEDA_WAKTU_MAKS = 610;

    protected function configure()
    {
        $this
            ->setName('sisdik:kehadiran:pesan')
            ->setDescription('Mengirim pesan kehadiran siswa.')
            ->addOption('paksa', null, InputOption::VALUE_NONE, 'Memaksa pengiriman pesan kehadiran untuk hari ini')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Menampilkan informasi debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smsKehadiranTerproses = 0;

        /* @var $em EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale("id_ID");

        $text = '';
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $waktuSekarang = new \DateTime();
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        if ($input->getOption('paksa')) {
            $jam = '09:00:00';
            $waktuSekarang = new \DateTime(date("Y-m-d $jam"));
            $mingguanHariKe = 0; // 0 = senin
            $bulananHariKe = 1;

            print "[paksa]: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe\n";
        }

        $qbSekolah = $em->createQueryBuilder()
            ->select('sekolah')
            ->from('LanggasSisdikBundle:Sekolah', 'sekolah')
        ;
        $semuaSekolah = $qbSekolah->getQuery()->getResult();

        foreach ($semuaSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'aktif' => true,
                    ])
                ;

                $qbKehadiranSemua = $em->createQueryBuilder()
                    ->select('COUNT(kehadiran.id)')
                    ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                    ->where('kehadiran.sekolah = :sekolah')
                    ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                    ->andWhere('kehadiran.tanggal = :tanggal')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunakademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                ;
                $jumlahKehadiranSemua = $qbKehadiranSemua->getQuery()->getSingleScalarResult();

                $qbKehadiranPermulaan = $em->createQueryBuilder()
                    ->select('COUNT(kehadiran.id)')
                    ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                    ->where('kehadiran.sekolah = :sekolah')
                    ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                    ->andWhere('kehadiran.tanggal = :tanggal')
                    ->andWhere('kehadiran.permulaan = :permulaan')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunakademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                    ->setParameter('permulaan', true)
                ;
                $jumlahKehadiranPermulaan = $qbKehadiranPermulaan->getQuery()->getSingleScalarResult();

                // jangan kirim pesan jika semua data kehadiran masih berstatus permulaan
                if ($jumlahKehadiranSemua == $jumlahKehadiranPermulaan) {
                    continue;
                }

                foreach ($perulangan as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()
                        ->select('jadwal')
                        ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwal')
                        ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
                        ->where('jadwal.sekolah = :sekolah')
                        ->andWhere('jadwal.smsJam = :jam')
                        ->andWhere('jadwal.perulangan = :perulangan')
                        ->andWhere('jadwal.kirimSms = :kirimsms')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->setParameter('sekolah', $sekolah->getId())
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

                    $jadwalKehadiran = $querybuilder->getQuery()->getResult();

                    foreach ($jadwalKehadiran as $jadwal) {
                        if (!(is_object($jadwal) && $jadwal instanceof JadwalKehadiran)) {
                            continue;
                        }

                        $jenisLayananSms = 'tak-terdefinisi';
                        switch ($jadwal->getStatusKehadiran()) {
                            case 'a-hadir-tepat':
                                $jenisLayananSms = 'l-kehadiran-tepat';
                                break;
                            case 'b-hadir-telat':
                                $jenisLayananSms = 'm-kehadiran-telat';
                                break;
                            case 'c-alpa':
                                $jenisLayananSms = 'k-kehadiran-alpa';
                                break;
                            case 'd-izin':
                                $jenisLayananSms = 'n-kehadiran-izin';
                                break;
                            case 'e-sakit':
                                $jenisLayananSms = 'o-kehadiran-sakit';
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
                            $qbKehadiran = $em->createQueryBuilder()
                                ->select('kehadiran')
                                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                                ->where('kehadiran.sekolah = :sekolah')
                                ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                                ->andWhere('kehadiran.kelas = :kelas')
                                ->andWhere('kehadiran.tanggal = :tanggal')
                                ->andWhere('kehadiran.statusKehadiran = :statuskehadiran')
                                ->andWhere('kehadiran.smsTerproses = :terproses')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunakademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas())
                                ->setParameter('tanggal', $waktuSekarang->format("Y-m-d"))
                                ->setParameter('statuskehadiran', $jadwal->getStatusKehadiran())
                                ->setParameter('terproses', false)
                            ;
                            $entitiesKehadiran = $qbKehadiran->getQuery()->getResult();

                            foreach ($entitiesKehadiran as $kehadiran) {
                                if (!(is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa)) {
                                    continue;
                                }

                                $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                                    ->findOneBy([
                                        'siswa' => $kehadiran->getSiswa(),
                                        'aktif' => true,
                                    ])
                                ;

                                if ((is_object($ortuWaliAktif) && $ortuWaliAktif instanceof OrangtuaWali)) {
                                    $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                                    if ($ponselOrtuWaliAktif != "") {
                                        $tekstemplate = $jadwal->getTemplatesms()->getTeks();
                                        $tekstemplate = str_replace("%nama%", $kehadiran->getSiswa()->getNamaLengkap(), $tekstemplate);
                                        $tekstemplate = str_replace("%nis%", $kehadiran->getSiswa()->getNomorInduk(), $tekstemplate);

                                        $indeksHari = $kehadiran->getTanggal()->format('w') - 1 == -1 ? 7 : $kehadiran->getTanggal()->format('w') - 1;
                                        $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$indeksHari]), $tekstemplate);

                                        $tekstemplate = str_replace("%tanggal%", $kehadiran->getTanggal()->format('d/m/Y'), $tekstemplate);

                                        if (!$kehadiran->isTervalidasi()) {
                                            $tekstemplate = str_replace("%jam%", $kehadiran->getJam(), $tekstemplate);
                                        } else {
                                            $tekstemplate = str_replace("%jam%", "", $tekstemplate);
                                        }

                                        $tekstemplate = str_replace("%keterangan%", $kehadiran->getKeteranganStatus(), $tekstemplate);

                                        $terkirim = false;
                                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWaliAktif);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->getContainer()->get('sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);

                                                if ($input->getOption('debug')) {
                                                    $messenger->populateMessage();
                                                    print "[debug]: " . $messenger->getMessageCommand() . "\n";
                                                } else {
                                                    $messenger->sendMessage($sekolah);
                                                }

                                                $smsKehadiranTerproses++;

                                                if ($input->getOption('paksa')) {
                                                    print $smsKehadiranTerproses . ", ";
                                                }

                                                $terkirim = true;
                                            }
                                        }

                                        if ($terkirim) {
                                            if (!$input->getOption('debug')) {
                                                $kehadiran->setSmsTerproses($terkirim);
                                                $em->persist($kehadiran);
                                            }
                                        }
                                    }
                                }
                            }

                            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                                ->findOneBy([
                                    'sekolah' => $sekolah,
                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                    'kelas' => $jadwal->getKelas(),
                                    'tanggal' => $waktuSekarang,
                                    'berhasilKirimSms' => false,
                                ])
                            ;

                            if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                                if (!$input->getOption('debug')) {
                                    $prosesKehadiranSiswa->setBerhasilKirimSms(true);
                                    $em->persist($prosesKehadiranSiswa);
                                }
                            }

                            if (!$input->getOption('debug')) {
                                $em->flush();
                            }
                        }
                    }
                }

                if ($input->getOption('debug')) {
                    $text .= "[debug]: SMS kehadiran terproses = $smsKehadiranTerproses";
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print "proses pengiriman pesan kehadiran sekolah " . $sekolah->getNama() . " telah dan sedang berjalan\n";
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
