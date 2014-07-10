<?php

namespace Langgas\SisdikBundle\Controller;
use Langgas\SisdikBundle\Form\SiswaApplicantPaymentSearchType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\PembayaranPendaftaran;
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

        $searchkey = '';
        $tampilkanTercari = false;

        $searchform = $this->createForm(new SiswaApplicantPaymentSearchType($this->container));

        $qbtotal = $em->createQueryBuilder()->select('COUNT(siswa.id)')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')->andWhere('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId());
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()->select('COUNT(DISTINCT siswa.id)')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->leftJoin('siswa.gelombang', 'gelombang')->leftJoin('siswa.sekolahAsal', 'sekolahasal')
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->andWhere('siswa.sekolah = :sekolah')
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran');
            $querybuilder->leftJoin('pembayaran.transaksiPembayaranPendaftaran', 'transaksi');

            $qbsearchnum->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran');
            $qbsearchnum->leftJoin('pembayaran.transaksiPembayaranPendaftaran', 'transaksi');

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('tahun.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());

                $qbsearchnum->andWhere('tahun.id = :tahun');
                $qbsearchnum->setParameter('tahun', $searchdata['tahun']->getId());

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap '
                                        . ' OR siswa.nomorPendaftaran LIKE :nomorpendaftaran '
                                        . ' OR siswa.keterangan LIKE :keterangan '
                                        . ' OR sekolahasal.nama LIKE :sekolahasal '
                                        . ' OR transaksi.nomorTransaksi = :nomortransaksi ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomorpendaftaran', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomortransaksi', $searchdata['searchkey']);

                $qbsearchnum
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap '
                                        . ' OR siswa.nomorPendaftaran = :nomorpendaftaran '
                                        . ' OR siswa.keterangan LIKE :keterangan '
                                        . ' OR sekolahasal.nama LIKE :sekolahasal '
                                        . ' OR transaksi.nomorTransaksi = :nomortransaksi ');
                $qbsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('nomorpendaftaran', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('nomortransaksi', $searchdata['searchkey']);

                $tampilkanTercari = true;
            }

            if ($searchdata['nopayment'] == true) {
                $querybuilder->andWhere("transaksi.nominalPembayaran IS NULL");

                $qbsearchnum->andWhere("transaksi.nominalPembayaran IS NULL");

                $tampilkanTercari = true;
            }

            if ($searchdata['todayinput'] == true) {
                $currentdate = new \DateTime();

                $querybuilder->andWhere("siswa.waktuSimpan BETWEEN :datefrom AND :dateto");
                $querybuilder->setParameter('datefrom', $currentdate->format('Y-m-d') . ' 00:00:00');
                $querybuilder->setParameter('dateto', $currentdate->format('Y-m-d') . ' 23:59:59');

                $qbsearchnum->andWhere("siswa.waktuSimpan BETWEEN :datefrom AND :dateto");
                $qbsearchnum->setParameter('datefrom', $currentdate->format('Y-m-d') . ' 00:00:00');
                $qbsearchnum->setParameter('dateto', $currentdate->format('Y-m-d') . ' 23:59:59');

                $tampilkanTercari = true;
            }

            if ($searchdata['notsettled'] == true) {
                $querybuilder
                        ->andWhere('siswa.sisaBiayaPendaftaran = -999 OR siswa.sisaBiayaPendaftaran != 0');

                $qbsearchnum
                        ->andWhere('siswa.sisaBiayaPendaftaran = -999 OR siswa.sisaBiayaPendaftaran != 0');

                $tampilkanTercari = true;
            }
        }

        $pendaftarTercari = $qbsearchnum->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'pendaftarTotal' => $pendaftarTotal, 'pendaftarTercari' => $pendaftarTercari,
                'tampilkanTercari' => $tampilkanTercari, 'searchkey' => $searchkey,
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.payments', array(), 'navigations')][$this->get('translator')->trans('links.applicant.payment', array(), 'navigations')]->setCurrent(true);
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
