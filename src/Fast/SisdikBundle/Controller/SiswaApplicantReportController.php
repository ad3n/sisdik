<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Form\FormError;
use Fast\SisdikBundle\Entity\Gelombang;
use Fast\SisdikBundle\Form\SiswaApplicantReportSearchType;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa laporan-pendaftaran controller.
 *
 * @Route("/laporan-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB')")
 */
class SiswaApplicantReportController extends Controller
{
    /**
     * Laporan pendaftaran siswa baru
     *
     * @Route("/", name="laporan-pendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $searchform = $this->createForm(new SiswaApplicantReportSearchType($this->container));

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('t.id'))
                ->from('FastSisdikBundle:Siswa', 't')->leftJoin('t.tahun', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);

        $qbAdvsearchnum = $em->createQueryBuilder()->select('COUNT(tcount.id)')
                ->from('FastSisdikBundle:Siswa', 'tcount');

        $qbBiaya = $em->createQueryBuilder()->select('SUM(t.nominal)')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't');
        $biaya = 0;

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2])
                ->orderBy('t2.tahun', 'DESC')->addOrderBy('t3.urutan', 'DESC')
                ->addOrderBy('t.nomorUrutPendaftaran', 'DESC');

        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchform->submit($this->getRequest());

        $searchdata = $searchform->getData();
        if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true
                && !($searchdata['gelombang'] instanceof Gelombang)) {
            $message = $this->get('translator')
                    ->trans('persentase hanya bisa digunakan untuk gelombang tertentu');
            $searchform->get('jumlahBayar')->addError(new FormError($message));
        }

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('t.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbAdvsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbBiaya->andWhere('t.gelombang = :gelombang');
                $qbBiaya->setParameter('gelombang', $searchdata['gelombang']->getId());
                $biaya = $qbBiaya->getQuery()->getSingleScalarResult();

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                if (is_numeric($searchdata['searchkey'])) {
                    $querybuilder->andWhere('t.nomorPendaftaran = :nomor');
                    $querybuilder->setParameter('nomor', $searchdata['searchkey']);

                    $qbsearchnum->andWhere('t.nomorPendaftaran = :nomor');
                    $qbsearchnum->setParameter('nomor', $searchdata['searchkey']);

                    $qbAdvsearchnum->setParameter('nomor', $searchdata['searchkey']);
                } else {
                    $querybuilder
                            ->andWhere(
                                    't.namaLengkap LIKE :namalengkap OR t.keterangan LIKE :keterangan OR t4.nama LIKE :sekolahasal OR t.alamat LIKE :alamat');
                    $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                    $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                    $querybuilder->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                    $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");

                    $qbsearchnum
                            ->andWhere(
                                    't.namaLengkap LIKE :namalengkap OR t.keterangan LIKE :keterangan OR t4.nama LIKE :sekolahasal OR t.alamat LIKE :alamat');
                    $qbsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                    $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                    $qbsearchnum->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                    $qbsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");

                    $qbAdvsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                    $qbAdvsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                    $qbAdvsearchnum->setParameter('sekolahasal', "%{$searchdata['searchkey']}%");
                    $qbAdvsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                }
                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('t.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan >= :daritanggal');
                $qbsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('daritanggal', $dariTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('t.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbsearchnum->andWhere('t.waktuSimpan <= :hinggatanggal');
                $qbsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $qbAdvsearchnum->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d"));

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('t.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbsearchnum->andWhere('t.jenisKelamin = :jeniskelamin');
                $qbsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $qbAdvsearchnum->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('t4.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbsearchnum->andWhere('t4.id = :sekolahasal');
                $qbsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $qbAdvsearchnum->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    if ($searchdata['jumlahBayar'] == 100 && $pembandingBayar != '<'
                            && $pembandingBayar != '<=') {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->andWhere('t.lunasBiayaPendaftaran = :lunas');
                        $querybuilder->setParameter('lunas', true);

                        $qbAdvsearchnum->setParameter('lunas', true);
                    } else {
                        $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                        $querybuilder->groupBy('t.id')
                                ->having(
                                        "SUM(t5.nominalTotal) + SUM(t5.nominalPotongan) + SUM(t5.persenPotonganDinominalkan) $pembandingBayar :jumlahbayar");
                        $querybuilder->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);

                        $qbAdvsearchnum
                                ->setParameter('jumlahbayar', $biaya * $searchdata['jumlahBayar'] / 100);
                    }

                } else {

                    $querybuilder->leftJoin('t.pembayaranPendaftaran', 't5');
                    $querybuilder->groupBy('t.id')
                            ->having("SUM(t5.nominalTotal) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                    $qbAdvsearchnum->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

                $qbAdvsearchnum->where($qbe->expr()->in('tcount.id', $querybuilder->getDQL()));
                $qbAdvsearchnum->setParameter('calon', true)->setParameter('sekolah', $sekolah->getId())
                        ->setParameter('tahunaktif', $panitiaAktif[2]);
                $pendaftarTercari = $qbAdvsearchnum->getQuery()->getSingleScalarResult();

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
            if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $pendaftarTercari = $pendaftarTercari != "" ? $pendaftarTercari
                : $qbsearchnum->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'panitiaAktif' => $panitiaAktif, 'pendaftarTotal' => $pendaftarTotal,
                'pendaftarTercari' => $pendaftarTercari, 'tampilkanTercari' => $tampilkanTercari,
                'pencarianLanjutan' => $pencarianLanjutan,
        );
    }

    /**
     * Finds and displays a Siswa laporan-pendaftaran entity.
     *
     * @Route("/{id}", name="laporan-pendaftaran_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(), 'panitiaAktif' => $panitiaAktif,
        );
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    /**
     * Mencari panitia pendaftaran aktif
     * Fungsi ini mengembalikan array berisi
     * index 0: daftar id panitia aktif
     * index 1: id ketua panitia aktif
     * index 2: id tahun panitia aktif
     * index 3: string tahun panitia aktif
     *
     * @return array panitiaaktif
     */
    private function getPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->leftJoin('t.tahun', 't2')->where('t.sekolah = :sekolah')->andWhere('t.aktif = 1')
                ->orderBy('t2.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        $panitiaaktif = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $panitiaaktif[0] = $entity->getPanitia();
                $panitiaaktif[1] = $entity->getKetuaPanitia()->getId();
                $panitiaaktif[2] = $entity->getTahun()->getId();
                $panitiaaktif[3] = $entity->getTahun()->getTahun();
            }
        }

        return $panitiaaktif;
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.pendaftaran']['links.laporan.pendaftaran']->setCurrent(true);
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
