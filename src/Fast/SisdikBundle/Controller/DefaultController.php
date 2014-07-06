<?php
namespace Fast\SisdikBundle\Controller;

use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Fast\SisdikBundle\Entity\TahunAkademik;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\KalenderPendidikan;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 * @Route("/")
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        $securityContext = $this->container->get('security.context');

        if ($securityContext->isGranted([new Expression('hasRole("ROLE_SISWA") and not hasAnyRole("ROLE_SUPER_ADMIN", "ROLE_WALI_KELAS")')])) {
            $response = $this->forward('FastSisdikBundle:Default:siswa');
        } elseif ($securityContext->isGranted([new Expression('hasRole("ROLE_SUPER_ADMIN")')])) {
            $response = $this->forward('FastSisdikBundle:Default:super');
        } else {
            $response = $this->forward('FastSisdikBundle:Default:pengelola');
        }

        return $response;
    }

    /**
     * @Template()
     */
    public function superAction()
    {

    }

    /**
     * @Template()
     */
    public function pengelolaAction()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademikAktif = $em->getRepository('FastSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $panitiaPendaftaranAktif = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $personilPanitiaPendaftaranAktif = null;
        if (is_object($panitiaPendaftaranAktif) && $panitiaPendaftaranAktif instanceof PanitiaPendaftaran) {
            $ketuaPanitiaPendaftaranAktif = $panitiaPendaftaranAktif->getKetuaPanitia()->getName();

            $tempArray = [];
            foreach ($panitiaPendaftaranAktif->getPanitia() as $personil) {
                $entity = $em->getRepository('FastSisdikBundle:User')->find($personil);

                if ($entity instanceof User) {
                    $tempArray[] = $entity->getName();
                } else {
                    $tempArray[] = $this->get('translator')->trans('label.username.undefined');
                }
            }
            $personilPanitiaPendaftaranAktif = implode(", ", $tempArray);
        }

        return [
            'tahunAkademikAktif' => $tahunAkademikAktif,
            'panitiaPendaftaranAktif' => $panitiaPendaftaranAktif,
            'personilPanitiaPendaftaranAktif' => $personilPanitiaPendaftaranAktif,
        ];
    }

    /**
     * @Template()
     */
    public function siswaAction()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademikAktif = $em->getRepository('FastSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $kehadiran = null;
        $tanggalSekarang = new \DateTime();

        $kalenderPendidikan = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
            ->findOneBy([
                'sekolah' => $sekolah,
                'tanggal' => $tanggalSekarang,
                'kbm' => true,
            ])
        ;

        $siswa = $this->getUser()->getSiswa();

        if (is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan) {
            $kehadiran = $em->getRepository('FastSisdikBundle:KehadiranSiswa')
                ->findOneBy([
                    'siswa' => $siswa,
                    'tanggal' => $tanggalSekarang,
                ])
            ;
        }

        return [
            'tahunAkademikAktif' => $tahunAkademikAktif,
            'siswa' => $siswa,
            'kehadiran' => $kehadiran,
            'daftarStatusKehadiran' => JadwalKehadiran::getDaftarStatusKehadiran(),
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
