<?php
namespace Fast\SisdikBundle\Controller;
use Symfony\Bundle\DoctrineBundle\Registry;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SekolahList
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    private function getSekolah() {
        $em = $this->container->get('doctrine')->getManager();
        $sekolah = $em->getRepository('FastSisdikBundle:Sekolah')->createQueryBuilder('t')
                ->orderBy('t.nama', 'ASC')->getQuery()->getResult();

        return $sekolah;
    }

    /**
     * build Sekolah for regular listing
     */
    public function buildSekolahList() {
        $choices = array();

        $sekolah = $this->getSekolah();

        foreach ($sekolah as $id) {
            $choices[$id->getId()] = $id->getNama();
        }

        return $choices;
    }

    /**
     * build Sekolah for user listing
     */
    public function buildSekolahUserList() {
        $choices = array();

        $sekolah = $this->getSekolah();

        $choices['all'] = $this->container->get('translator')->trans('label.all');

        foreach ($sekolah as $id) {
            $choices[$id->getId()] = $id->getNama();
        }

        $choices['unset'] = $this->container->get('translator')->trans('label.unset');

        return $choices;
    }

    /**
     * build Sekolah for statuskehadirankepulangan listing
     */
    public function buildSekolahStatusKehadiranKepulanganList() {
        $choices = array();

        $sekolah = $this->getSekolah();

        $choices['all'] = $this->container->get('translator')->trans('label.all');

        foreach ($sekolah as $id) {
            $choices[$id->getId()] = $id->getNama();
        }

        return $choices;
    }
}
