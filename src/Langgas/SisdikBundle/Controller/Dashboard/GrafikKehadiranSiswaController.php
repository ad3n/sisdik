<?php

namespace Langgas\SisdikBundle\Controller\Dashboard;

use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
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
        $kehadiran = [];
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
        $kehadiranSiswaTotal = $kehadiran;

        $kehadiranSiswaPerKelas = [];
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
                    } else {
                        foreach ($daftarStatusKehadiran as $key => $val) {
                            $kehadiran[$key] = 0;
                        }
                    }
                    $kehadiranSiswaPerKelas[$kelas->getId()] = $kehadiran;
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
            'daftarTingkat' => $daftarTingkat,
            'daftarKelasPerTingkat' => $daftarKelasPerTingkat,
            'kehadiranSiswaTotal' => $kehadiranSiswaTotal,
            'kehadiranSiswaPerKelas' => $kehadiranSiswaPerKelas,
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
