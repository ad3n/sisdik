<?php

namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Util\Calendar;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/tabulasi-kehadiran-siswa")
 * @PreAuthorize("hasRole('ROLE_SISWA')")
 */
class TabulasiKehadiranSiswaController extends Controller
{
    /**
     * @Route("/{year}/{month}", name="siswa__kehadiran")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:tabulasi.html.twig")
     */
    public function indexAction($year = 0, $month = 0)
    {
        $waktuSekarang = new \DateTime();
        $year = $year != 0 ? $year : $waktuSekarang->format('Y');
        $month = $month != 0 ? $month : $waktuSekarang->format('m');

        $tanggalTerpilih = new \DateTime("$year-$month-01");

        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;
        if (!$tahunAkademik instanceof TahunAkademik) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        $siswa = $this->getUser()->getSiswa();

        $siswaKelas = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
            ->findOneBy([
                'siswa' => $siswa,
                'tahunAkademik' => $tahunAkademik,
                'aktif' => true,
            ])
        ;
        if (!$siswaKelas instanceof SiswaKelas) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.siswa.tidak.terdaftar.aktif.di.kelas'));
        }

        $objectCalendar = new Calendar();
        $calendar = $objectCalendar->createMonthlyCalendar($tanggalTerpilih->format('Y'), $tanggalTerpilih->format('m'));

        $nextmonth = date('Y-m-d', mktime(0, 0, 0, $tanggalTerpilih->format('m') + 1, 1, $tanggalTerpilih->format('Y')));

        $kehadiran = $em->createQueryBuilder()
            ->select('kehadiran')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->where('kehadiran.sekolah = :sekolah')
            ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
            ->andWhere('kehadiran.kelas = :kelas')
            ->andWhere('kehadiran.siswa = :siswa')
            ->andWhere('kehadiran.tanggal >= :firstday AND kehadiran.tanggal < :nextmonth')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
            ->setParameter('kelas', $siswaKelas->getKelas())
            ->setParameter('siswa', $siswa)
            ->setParameter('firstday', $tanggalTerpilih->format('Y-m-01'))
            ->setParameter('nextmonth', $nextmonth)
            ->getQuery()
            ->getResult()
        ;

        $tanggalTerpilih->modify('first day of -1 month');
        $tahunBulanSebelumnya = $tanggalTerpilih->format('Y');
        $bulanSebelumnya = $tanggalTerpilih->format('m');

        $tanggalTerpilih->modify('first day of +2 month');
        $tahunBulanBerikutnya = $tanggalTerpilih->format('Y');
        $bulanBerikutnya = $tanggalTerpilih->format('m');

        return [
            'tahunAkademik' => $tahunAkademik,
            'kelas' => $siswaKelas,
            'siswa' => $siswa,
            'kehadiran' => $kehadiran,
            'daftarStatusKehadiran' => JadwalKehadiran::getDaftarStatusKehadiran(),
            'calendar' => $calendar,
            'tanggalTerpilih' => $tanggalTerpilih,
            'tahunBulanSebelumnya' => $tahunBulanSebelumnya,
            'bulanSebelumnya' => $bulanSebelumnya,
            'tahunBulanBerikutnya' => $tahunBulanBerikutnya,
            'bulanBerikutnya' => $bulanBerikutnya,
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
