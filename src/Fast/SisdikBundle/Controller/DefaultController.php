<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Fast\SisdikBundle\Entity\Tahun;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{

    /**
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->getSchool();

        $em = $this->getDoctrine()->getManager();

        if ($sekolah) {
            $tahun = $em->getRepository('FastSisdikBundle:Tahun')
                    ->findOneBy(
                            array(
                                'sekolah' => $sekolah->getId(), 'aktif' => true
                            ));
            $tahunaktif = (is_object($tahun) && $tahun instanceof Tahun) ? $tahun->getNama() : null;

            $panitiaPendaftaran = $em->getRepository('FastSisdikBundle:PanitiaPendaftaran')
                    ->findOneBy(
                            array(
                                'sekolah' => $sekolah->getId(), 'aktif' => true
                            ));
            if (is_object($panitiaPendaftaran) && $panitiaPendaftaran instanceof PanitiaPendaftaran) {
                $ketuaPanitiaPendaftaranAktif = $panitiaPendaftaran->getKetuaPanitia()->getName();

                $tempArray = array();
                foreach ($panitiaPendaftaran->getPanitia() as $personil) {
                    $entity = $em->getRepository('FastSisdikBundle:User')->find($personil);

                    if ($entity instanceof User) {
                        $tempArray[] = $entity->getName();
                    } else {
                        $tempArray[] = $this->get('translator')->trans('label.username.undefined');
                    }
                }
                $panitiaPendaftaranAktif = implode(", ", $tempArray);

            } else {
                $ketuaPanitiaPendaftaranAktif = null;
                $panitiaPendaftaranAktif = null;
            }
        } else {
            $tahunaktif = null;
            $ketuaPanitiaPendaftaranAktif = null;
            $panitiaPendaftaranAktif = null;
        }

        return array(
                'tahunaktif' => $tahunaktif, 'ketuaPanitiaPendaftaranAktif' => $ketuaPanitiaPendaftaranAktif,
                'panitiaPendaftaranAktif' => $panitiaPendaftaranAktif,
        );
    }

    private function getSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else {
            return false;
        }
    }
}
