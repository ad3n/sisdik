<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaApplicantPaymentSearchType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\PembayaranPendaftaran;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa applicant controller.
 *
 * @Route("/applicant/payment")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
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
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.pembayaranPendaftaran', 't5')
                ->leftJoin('t5.transaksiPembayaranPendaftaran', 't6')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('t2.sekolah = :sekolah')->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorUrutPendaftaran', 'DESC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t2.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap '
                                        . ' OR t.nomorPendaftaran = :nomorpendaftaran '
                                        . ' OR t.keterangan LIKE :keterangan '
                                        . ' OR t4.nama LIKE :sekolahasal '
                                        . ' OR t6.nomorTransaksi = :nomortransaksi ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomorpendaftaran', $searchdata['searchkey']);
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomortransaksi', $searchdata['searchkey']);
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
                $querybuilder->andWhere("t.lunasBiayaPendaftaran = :lunas");
                $querybuilder->setParameter('lunas', false);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView(),
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.applicant.payment']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
