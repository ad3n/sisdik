<?php

namespace Langgas\SisdikBundle\Controller\Dashboard;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/grafik-kehadiran-siswa")
 */
class GrafikKehadiranSiswaController extends Controller
{
    /**
     * @Route("/{tanggal}", name="siswa__kehadiran_grafik")
     * @Template()
     */
    public function indexAction($tanggal = null)
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $tanggalTampil = new \DateTime($tanggal);
        $tanggalSebelumnya = $tanggalTampil->modify('-1 day')->format('Y-m-d');
        $tanggalBerikutnya = $tanggalTampil->modify('+2 day')->format('Y-m-d');
        $tanggalTampil->modify('-1 day');

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $daftarStatusKepulangan = JadwalKepulangan::getDaftarStatusKepulangan();

        $daftarTingkat = $em->getRepository('LanggasSisdikBundle:Tingkat')
            ->findBy([
                'sekolah' => $sekolah,
            ], [
                'nama' => 'ASC',
                'kode' => 'ASC',
                'urutan' => 'ASC',
            ])
        ;

        $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
            ->findOneBy([
                'sekolah' => $sekolah,
                'tanggal' => $tanggalTampil,
                'kbm' => true,
            ])
        ;

        $kehadiranSiswaTotal = null;
        $kepulanganSiswaTotal = null;
        $kehadiran = [];
        $kepulangan = [];
        if (is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan) {
            foreach ($daftarStatusKehadiran as $key => $val) {
                $result = $em->createQueryBuilder()
                    ->select('COUNT(kehadiranSiswa.id)')
                    ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
                    ->where('kehadiranSiswa.sekolah = :sekolah')
                    ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
                    ->andWhere('kehadiranSiswa.tanggal = :tanggal')
                    ->andWhere('kehadiranSiswa.statusKehadiran = :statusKehadiran')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunAkademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $tanggalTampil->format("Y-m-d"))
                    ->setParameter('statusKehadiran', $key)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
                $kehadiran[$key] = $result;
            }

            foreach ($daftarStatusKepulangan as $key => $val) {
                $result = $em->createQueryBuilder()
                    ->select('COUNT(kepulanganSiswa.id)')
                    ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulanganSiswa')
                    ->where('kepulanganSiswa.sekolah = :sekolah')
                    ->andWhere('kepulanganSiswa.tahunAkademik = :tahunAkademik')
                    ->andWhere('kepulanganSiswa.tanggal = :tanggal')
                    ->andWhere('kepulanganSiswa.statusKepulangan = :statusKepulangan')
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('tahunAkademik', $tahunAkademikAktif)
                    ->setParameter('tanggal', $tanggalTampil->format("Y-m-d"))
                    ->setParameter('statusKepulangan', $key)
                    ->getQuery()
                    ->useResultCache(true, 3600)
                    ->getSingleScalarResult()
                ;
                $kepulangan[$key] = $result;
            }
        } else {
            foreach ($daftarStatusKehadiran as $key => $val) {
                $kehadiran[$key] = 0;
            }

            foreach ($daftarStatusKepulangan as $key => $val) {
                $kepulangan[$key] = 0;
            }
        }
        $kehadiranSiswaTotal = $kehadiran;
        $kepulanganSiswaTotal = $kepulangan;

        $kehadiranSiswaPerKelas = [];
        $kepulanganSiswaPerKelas = [];
        foreach ($daftarTingkat as $tingkat) {
            $daftarKelas = $em->getRepository('LanggasSisdikBundle:Kelas')
                ->findBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademikAktif,
                    'tingkat' => $tingkat,
                ], [
                    'nama' => 'ASC',
                    'kode' => 'ASC',
                    'urutan' => 'ASC',
                ])
            ;

            $daftarKelasPerTingkat[$tingkat->getId()] = $daftarKelas;

            foreach ($daftarKelas as $kelas) {
                if ($kelas instanceof Kelas) {
                    $kehadiran = [];
                    $kepulangan = [];
                    if (is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan) {
                        foreach ($daftarStatusKehadiran as $key => $val) {
                            $result = $em->createQueryBuilder()
                                ->select('COUNT(kehadiranSiswa.id)')
                                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
                                ->where('kehadiranSiswa.sekolah = :sekolah')
                                ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
                                ->andWhere('kehadiranSiswa.kelas = :kelas')
                                ->andWhere('kehadiranSiswa.tanggal = :tanggal')
                                ->andWhere('kehadiranSiswa.statusKehadiran = :statusKehadiran')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunAkademik', $tahunAkademikAktif)
                                ->setParameter('kelas', $kelas)
                                ->setParameter('tanggal', $tanggalTampil->format("Y-m-d"))
                                ->setParameter('statusKehadiran', $key)
                                ->getQuery()
                                ->getSingleScalarResult()
                            ;
                            $kehadiran[$key] = $result;
                        }

                        foreach ($daftarStatusKepulangan as $key => $val) {
                            $result = $em->createQueryBuilder()
                                ->select('COUNT(kepulanganSiswa.id)')
                                ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulanganSiswa')
                                ->where('kepulanganSiswa.sekolah = :sekolah')
                                ->andWhere('kepulanganSiswa.tahunAkademik = :tahunAkademik')
                                ->andWhere('kepulanganSiswa.kelas = :kelas')
                                ->andWhere('kepulanganSiswa.tanggal = :tanggal')
                                ->andWhere('kepulanganSiswa.statusKepulangan = :statusKepulangan')
                                ->setParameter('sekolah', $sekolah)
                                ->setParameter('tahunAkademik', $tahunAkademikAktif)
                                ->setParameter('kelas', $kelas)
                                ->setParameter('tanggal', $tanggalTampil->format("Y-m-d"))
                                ->setParameter('statusKepulangan', $key)
                                ->getQuery()
                                ->useResultCache(true, 3600)
                                ->getSingleScalarResult()
                            ;
                            $kepulangan[$key] = $result;
                        }
                    } else {
                        foreach ($daftarStatusKehadiran as $key => $val) {
                            $kehadiran[$key] = 0;
                        }

                        foreach ($daftarStatusKepulangan as $key => $val) {
                            $kepulangan[$key] = 0;
                        }
                    }
                    $kehadiranSiswaPerKelas[$kelas->getId()] = $kehadiran;
                    $kepulanganSiswaPerKelas[$kelas->getId()] = $kepulangan;
                }
            }
        }

        $searchform = $this->createForm('sisdik_kehadiransiswasearch');
        $searchform->setData(['tanggal' => $tanggalTampil]);

        return [
            'tahunAkademikAktif' => $tahunAkademikAktif,
            'tanggalTampil' => $tanggalTampil,
            'tanggalSebelumnya' => $tanggalSebelumnya,
            'tanggalBerikutnya' => $tanggalBerikutnya,
            'daftarStatusKehadiran' => $daftarStatusKehadiran,
            'daftarStatusKepulangan' => $daftarStatusKepulangan,
            'daftarTingkat' => $daftarTingkat,
            'daftarKelasPerTingkat' => $daftarKelasPerTingkat,
            'kehadiranSiswaTotal' => $kehadiranSiswaTotal,
            'kepulanganSiswaTotal' => $kepulanganSiswaTotal,
            'kehadiranSiswaPerKelas' => $kehadiranSiswaPerKelas,
            'kepulanganSiswaPerKelas' => $kepulanganSiswaPerKelas,
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
