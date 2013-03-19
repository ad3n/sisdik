<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaApplicantPaymentSearchType;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\PembayaranPendaftaran;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Siswa applicant controller.
 *
 * @Route("/applicant/payment")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KETUA_PANITIA_PSB')")
 */
class SiswaApplicantPaymentController extends Controller
{

    /**
     * Lists all Siswa applicant entities.
     *
     * @Route("/", name="applicant_payment")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SiswaApplicantPaymentSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahunmasuk', 't2')->leftJoin('t.gelombang', 't3')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t2.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorPendaftaran', 'DESC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :tahunmasuk');
                $querybuilder->setParameter('tahunmasuk', $searchdata['tahunmasuk']->getId());
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran = :nomorpendaftaran');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomorpendaftaran', $searchdata['searchkey']);
            }

            if ($searchdata['nopayment'] == true) {
                $querybuilder->leftJoin('t.pembayaranPendaftaran', 'pp');
                $querybuilder->leftJoin('pp.transaksiPembayaranPendaftaran', 'tpp');
                $querybuilder->andWhere("tpp.nominalPembayaran IS NULL");
            }

            if ($searchdata['todayinput'] == true) {
                $querybuilder->andWhere("t.waktuSimpan BETWEEN :datefrom AND :dateto");
                $currentdate = new \DateTime();
                $querybuilder->setParameter('datefrom', $currentdate->format('Y-m-d') . ' 00:00:00');
                $querybuilder->setParameter('dateto', $currentdate->format('Y-m-d') . ' 23:59:59');
            }

            if ($searchdata['notsettled'] == true) {
                $results = $querybuilder->getQuery()->getResult();

                $daftarSiswaBelumLunas = array();
                $totalBiaya = 0;
                $nominalBiaya = 0;
                foreach ($results as $entity) {
                    $totalBiaya = 0;
                    if (is_object($entity) && $entity instanceof Siswa) {
                        $totalPembayaranPendaftaran = $entity->getTotalNominalPembayaranPendaftaran();
                        foreach ($entity->getPembayaranPendaftaran() as $pembayaran) {
                            $nominalBiaya = 0;
                            if ($pembayaran instanceof PembayaranPendaftaran) {
                                foreach ($pembayaran->getDaftarBiayaPendaftaran() as $biaya) {
                                    $biayaPendaftaran = $em
                                            ->getRepository('FastSisdikBundle:BiayaPendaftaran')
                                            ->find($biaya);
                                    $nominalBiaya += $biayaPendaftaran->getNominal();
                                }
                                $totalBiaya += $nominalBiaya;
                            }
                        }
                        if ($totalPembayaranPendaftaran < $totalBiaya || $totalPembayaranPendaftaran == 0) {
                            $daftarSiswaBelumLunas[] = $entity->getId();
                        }
                    }
                }

                if (count($daftarSiswaBelumLunas) > 0) {
                    $querybuilder->andWhere("t.id IN (?1)")->setParameter(1, $daftarSiswaBelumLunas);
                }

            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.applicant.payment']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
