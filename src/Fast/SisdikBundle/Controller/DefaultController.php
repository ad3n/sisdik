<?php

namespace Fast\SisdikBundle\Controller;
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
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahun', 't')
                    ->where('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah);
            $results = $querybuilder->getQuery()->getResult();
            foreach ($results as $entity) {
                $tahunaktif = $entity->getNama();
            }
        } else {
            $tahunaktif = null;
        }

        return array(
            'tahunaktif' => $tahunaktif
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
