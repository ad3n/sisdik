<?php
namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Util\Calendar;
use Langgas\SisdikBundle\Entity\SiswaKelas;
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
            return $this->redirect($this->generateUrl('siswa__kehadiran'));
        } elseif ($securityContext->isGranted([new Expression('hasRole("ROLE_SUPER_ADMIN")')])) {
            $response = $this->forward('LanggasSisdikBundle:Default:super');
        } else {
            $response = $this->forward('LanggasSisdikBundle:Default:pengelola');
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

        $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        $panitiaPendaftaranAktif = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')
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
                $entity = $em->getRepository('LanggasSisdikBundle:User')->find($personil);

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
