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

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->where('siswa.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('tahun.sekolah = :sekolah')
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('tahun.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap '
                                        . ' OR siswa.nomorPendaftaran = :nomorpendaftaran '
                                        . ' OR siswa.keterangan LIKE :keterangan '
                                        . ' OR sekolahasal.nama LIKE :sekolahasal '
                                        . ' OR transaksi.nomorTransaksi = :nomortransaksi ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomorpendaftaran', $searchdata['searchkey']);
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomortransaksi', $searchdata['searchkey']);
            }

            if ($searchdata['nopayment'] == true) {
                $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran');
                $querybuilder->leftJoin('pembayaran.transaksiPembayaranPendaftaran', 'transaksi');
                $querybuilder->andWhere("transaksi.nominalPembayaran IS NULL");
            }

            if ($searchdata['todayinput'] == true) {
                $querybuilder->andWhere("siswa.waktuSimpan BETWEEN :datefrom AND :dateto");
                $currentdate = new \DateTime();
                $querybuilder->setParameter('datefrom', $currentdate->format('Y-m-d') . ' 00:00:00');
                $querybuilder->setParameter('dateto', $currentdate->format('Y-m-d') . ' 23:59:59');
            }

            // the following lines are wrong, due to database refactoring
            if ($searchdata['notsettled'] == true) {
                $querybuilder
                        ->andWhere('siswa.sisaBiayaPendaftaran = -999 OR siswa.sisaBiayaPendaftaran != 0');
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
