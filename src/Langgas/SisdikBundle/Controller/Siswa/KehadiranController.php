<?php
namespace Langgas\SisdikBundle\Controller\Siswa;

use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Util\Calendar;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/kehadiran")
 * @PreAuthorize("hasRole('ROLE_SISWA')")
 */
class KehadiranController extends Controller
{
    /**
     * @Route("/{year}/{month}", name="siswa__kehadiran")
     * @Template()
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

        $siswa = $this->getUser()->getSiswa();

        $siswaKelas = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
            ->findOneBy([
                'siswa' => $siswa,
                'tahunAkademik' => $tahunAkademik,
                'aktif' => true,
            ])
        ;

        $objectCalendar = new Calendar;
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

        $bulanSebelumnya = $tanggalTerpilih->modify('first day of -1 month')->format('m');
        $bulanBerikutnya = $tanggalTerpilih->modify('first day of +2 month')->format('m');

        return [
            'tahunAkademik' => $tahunAkademik,
            'kelas' => $siswaKelas,
            'siswa' => $siswa,
            'kehadiran' => $kehadiran,
            'daftarStatusKehadiran' => JadwalKehadiran::getDaftarStatusKehadiran(),
            'calendar' => $calendar,
            'tanggalTerpilih' => $tanggalTerpilih,
            'bulanSebelumnya' => $bulanSebelumnya,
            'bulanBerikutnya' => $bulanBerikutnya,
        ];
    }

    private function getSekolah()
    {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            return null;
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
