<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaTahkikType;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Fast\SisdikBundle\Entity\Gelombang;
use Fast\SisdikBundle\Form\SiswaTahkikSearchType;
use Fast\SisdikBundle\Entity\Referensi;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Form\SiswaApplicantType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa tahkik controller.
 *
 * @Route("/tahkik")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_KETUA_PANITIA_PSB')")
 */
class SiswaTahkikController extends Controller
{
    /**
     * Menampilkan form untuk mencari calon siswa yang akan ditahkikkan
     *
     * @Route("/", name="tahkik")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm(new SiswaTahkikSearchType($this->container));
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbtertahkik = $em->createQueryBuilder()->select($qbe->expr()->countDistinct('siswa.id'))
                ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.tahun', 'tahun')
                ->andWhere('siswa.calonSiswa = :calon')->setParameter('calon', false)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2]);
        $pendaftarTertahkik = $qbtertahkik->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.sekolahAsal', 'sekolahasal')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('tahun.id = :tahunaktif')->setParameter('tahunaktif', $panitiaAktif[2])
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        if ($searchform->isValid()) {

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder->andWhere('siswa.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan >= :daritanggal');
                $querybuilder->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"));

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('siswa.waktuSimpan <= :hinggatanggal');
                $querybuilder->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"));

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder->andWhere('siswa.jenisKelamin = :jeniskelamin');
                $querybuilder->setParameter('jeniskelamin', $searchdata['jenisKelamin']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder->andWhere('sekolahasal.id = :sekolahasal');
                $querybuilder->setParameter('sekolahasal', $searchdata['sekolahAsal']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder->leftJoin('siswa.referensi', 'ref');
                $querybuilder->andWhere('ref.id = :referensi');
                $querybuilder->setParameter('referensi', $searchdata['referensi']->getId());

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['tertahkik'] === true) {
                $querybuilder->andWhere('siswa.calonSiswa = :calonSiswa');
                $querybuilder->setParameter('calonSiswa', !$searchdata['tertahkik']);

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            } else {
                $querybuilder->andWhere('siswa.calonSiswa = :calon')->setParameter('calon', true);
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id');

                    if ($pembandingBayar == '<' || $pembandingBayar == '<='
                            || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        // masukkan pencarian untuk yg belum melakukan transaksi
                        $querybuilder
                                ->having(
                                        "(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                                . " OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0");
                    } else {
                        $querybuilder
                                ->having(
                                        "SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                                . " (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar");
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);

                } else {

                    $querybuilder->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')->groupBy('siswa.id')
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar");
                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar']);

                }

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

        $pendaftarTercari = count($querybuilder->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        $idsiswa = '';
        foreach ($pagination->getItems() as $item) {
            $idsiswa .= $item->getId() . ':';
        }

        $tahkikform = $this->createForm(new SiswaTahkikType($this->container, $idsiswa));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'tahkikform' => $tahkikform->createView(), 'panitiaAktif' => $panitiaAktif,
                'pendaftarTotal' => $pendaftarTotal, 'pendaftarTertahkik' => $pendaftarTertahkik,
                'pendaftarTercari' => $pendaftarTercari, 'tampilkanTercari' => $tampilkanTercari,
                'pencarianLanjutan' => $pencarianLanjutan, 'searchkey' => $searchkey, 'idsiswa' => $idsiswa,
        );
    }

    /**
     * Edits an existing Siswa tahkik entity.
     *
     * @Route("/{idsiswa}", name="tahkik_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaTahkik:index.html.twig")
     */
    public function updateAction(Request $request, $idsiswa = '') {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $tahkikform = $this->createForm(new SiswaTahkikType($this->container, $idsiswa));
        $tahkikform->submit($request);

        if ($tahkikform->isValid()) {
            $data = $tahkikform->getData();

            $entities = $em->getRepository('FastSisdikBundle:Siswa')
                    ->findBy(
                            array(
                                'id' => preg_split('/:/', $idsiswa)
                            ));
            foreach ($entities as $entity) {
                if (is_object($entity) && $entity instanceof Siswa) {
                    if (array_key_exists('siswa_' . $entity->getId(), $data) === true) {
                        if ($data['siswa_' . $entity->getId()] === true) {
                            $entity->setCalonSiswa(false);
                            $em->persist($entity);
                        }
                    }
                }
            }
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.penahkikan.terbarui'));
        }

        return $this->redirect($this->generateUrl('tahkik'));
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
    public function getPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('panitia')
                ->from('FastSisdikBundle:PanitiaPendaftaran', 'panitia')->leftJoin('panitia.tahun', 'tahun')
                ->where('panitia.sekolah = :sekolah')->andWhere('panitia.aktif = 1')
                ->orderBy('tahun.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())
                ->setMaxResults(1);
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
        $menu['headings.pendaftaran']['links.tahkik']->setCurrent(true);
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
