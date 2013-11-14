<?php

namespace Fast\SisdikBundle\Command;
use Symfony\Component\Translation\IdentityTranslator;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Fast\SisdikBundle\Util\Messenger;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author Ihsan Faisal
 */
class KirimSmsKehadiranCommand extends ContainerAwareCommand
{
    const LOCK_FILE = "kirim-sms-kehadiran.lock";
    const LOCK_DIR = "lock";

    protected function configure() {
        $this->setName('sisdik:kehadiran:kirim-sms')
                ->setDescription('Periksa dan jalankan inisiasi kehadiran.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
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

        $debug = true;
        if ($debug) {
            $jam = '08:00:00';
            $mingguanHariKe = 0; // 0 = senin
            $bulananHariKe = 1;

            print
                    "debug: periksa jadwal jam:$jam, mingguanHariKe:$mingguanHariKe, bulananHariKe:$bulananHariKe"
                            . "\n";
        }

        $qbSekolah = $em->createQueryBuilder()->select('sekolah')
                ->from('FastSisdikBundle:Sekolah', 'sekolah');
        $entitiesSekolah = $qbSekolah->getQuery()->getResult();
        foreach ($entitiesSekolah as $sekolah) {
            if (!(is_object($sekolah) && $sekolah instanceof Sekolah)) {
                continue;
            }

            if (!$this->isLocked($sekolah->getNomorUrut())) {
                foreach ($perulangan as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()->select('jadwal')
                            ->from('FastSisdikBundle:JadwalKehadiran', 'jadwal')
                            ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
                            ->where('jadwal.sekolah = :sekolah')->andWhere('jadwal.smsJam = :jam')
                            ->andWhere('jadwal.perulangan = :perulangan')
                            ->andWhere('jadwal.kirimSms = :kirimsms')
                            ->andWhere('tahunAkademik.aktif = :aktif')
                            ->setParameter('sekolah', $sekolah->getId())->setParameter('jam', $jam)
                            ->setParameter('perulangan', $key)->setParameter('kirimsms', true)
                            ->setParameter('aktif', true);
                    if ($key == 'b-mingguan') {
                        $querybuilder->andWhere('jadwal.mingguanHariKe = :harike')
                                ->setParameter('harike', $mingguanHariKe);
                    } elseif ($key == 'c-bulanan') {
                        $querybuilder->andWhere('jadwal.bulananHariKe = :tanggalke')
                                ->setParameter('tanggalke', $bulananHariKe);
                    }
                    $entities = $querybuilder->getQuery()->getResult();
                    foreach ($entities as $jadwal) {
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
                        $pilihanLayananSms = $em->getRepository('FastSisdikBundle:PilihanLayananSms')
                                ->findBy(
                                        array(
                                                'sekolah' => $sekolah->getId(),
                                                'jenisLayanan' => $jenisLayananSms,
                                        ));

                        $qbKehadiran = $em->createQueryBuilder()->select('kehadiran')
                                ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')
                                ->where('kehadiran.sekolah = :sekolah')
                                ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                                ->andWhere('kehadiran.kelas = :kelas')
                                ->andWhere('kehadiran.statusKehadiran = :statuskehadiran')
                                ->setParameter('sekolah', $sekolah->getId())
                                ->setParameter('tahunakademik', $jadwal->getTahunAkademik())
                                ->setParameter('kelas', $jadwal->getKelas())
                                ->setParameter('statuskehadiran', $jadwal->getStatusKehadiran());
                        $entitiesKehadiran = $qbKehadiran->getQuery()->getResult();
                        foreach ($entitiesKehadiran as $kehadiran) {
                            if (!(is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa)) {
                                continue;
                            }

                            $ponselOrtuWali = "";
                            foreach ($kehadiran->getSiswa()->getOrangtuaWali() as $orangtuaWali) {
                                if ($orangtuaWali instanceof OrangtuaWali) {
                                    if ($orangtuaWali->isAktif()) {
                                        $ponselOrtuWali = $orangtuaWali->getPonsel();
                                        break;
                                    }
                                }
                            }

                            $tekstemplate = $jadwal->getTemplatesms()->getTeks();

                            $tekstemplate = str_replace("%nama%", $kehadiran->getSiswa()->getNamaLengkap(),
                                    $tekstemplate);
                            $tekstemplate = str_replace("%nis%", $kehadiran->getSiswa()->getNomorInduk(),
                                    $tekstemplate);
                            $tekstemplate = str_replace("%hari%",
                                    /** @Ignore */ $translator->trans($namaNamaHari[$mingguanHariKe]), $tekstemplate);
                            $tekstemplate = str_replace("%tanggal%", $waktuSekarang->format('d/m/Y'),
                                    $tekstemplate);
                            $tekstemplate = str_replace("%jam%", $kehadiran->getJam(), $tekstemplate);

                            foreach ($pilihanLayananSms as $pilihan) {
                                if ($pilihan instanceof PilihanLayananSms) {
                                    if ($pilihan->getStatus()) {
                                        if ($ponselOrtuWali != "") {
                                            $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                            foreach ($nomorponsel as $ponsel) {
                                                $messenger = $this->getContainer()
                                                        ->get('fast_sisdik.messenger');
                                                if ($messenger instanceof Messenger) {
                                                    $messenger->setPhoneNumber($ponsel);
                                                    $messenger->setMessage($tekstemplate);
                                                    $messenger->sendMessage($sekolah);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($text != '') {
                    $output->writeln($text);
                }
            } else {
                print
                        "proses pengiriman sms kehadiran sekolah " . $sekolah->getNama()
                                . " telah dan sedang berjalan\n";
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
    private function isLocked($nomorUrutSekolah) {
        $lockfile = $this->getContainer()->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR . self::LOCK_DIR
                . DIRECTORY_SEPARATOR . $nomorUrutSekolah . '.' . self::LOCK_FILE;

        if (file_exists($lockfile)) {
            $lockingPID = trim(file_get_contents($lockfile));

            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            if (in_array($lockingPID, $pids))
                return true;

            print "Removing stale lock file.\n";
            unlink($lockfile);
        }

        file_put_contents($lockfile, getmypid() . "\n");

        return false;
    }
}
