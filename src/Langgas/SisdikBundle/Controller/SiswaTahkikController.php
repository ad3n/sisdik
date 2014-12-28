<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/tahkik")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_KETUA_PANITIA_PSB')")
 */
class SiswaTahkikController extends Controller
{
    /**
     * @Route("/", name="tahkik")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        $qbe = $em->createQueryBuilder();

        $pendaftarTercari = 0;
        $tampilkanTercari = false;
        $pencarianLanjutan = false;
        $searchkey = '';

        $searchform = $this->createForm('sisdik_caritahkik');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        $qbtotal = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
        ;
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbtertahkik = $em->createQueryBuilder()
            ->select($qbe->expr()->countDistinct('siswa.id'))
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->setParameter('calon', false)
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
        ;
        $pendaftarTertahkik = $qbtertahkik->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.sekolahAsal', 'sekolahasal')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = :tahunaktif')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('ortuaktif', true)
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunaktif', $panitiaAktif[2])
        ;

        if ($searchform->isValid()) {
            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap '
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan '
                        .' OR siswa.alamat LIKE :alamat '
                        .' OR orangtua.nama LIKE :namaortu '
                        .' OR orangtua.ponsel LIKE :ponselortu ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomor', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('alamat', "%{$searchdata['searchkey']}%")
                    ->setParameter('namaortu', "%{$searchdata['searchkey']}%")
                    ->setParameter('ponselortu', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            if ($dariTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan >= :daritanggal')
                    ->setParameter('daritanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                ;

                $tampilkanTercari = true;
            }

            $hinggaTanggal = $searchdata['hinggaTanggal'];
            if ($hinggaTanggal instanceof \DateTime) {
                $querybuilder
                    ->andWhere('siswa.waktuSimpan <= :hinggatanggal')
                    ->setParameter('hinggatanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['jenisKelamin'] != '') {
                $querybuilder
                    ->andWhere('siswa.jenisKelamin = :jeniskelamin')
                    ->setParameter('jeniskelamin', $searchdata['jenisKelamin'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['sekolahAsal'] instanceof SekolahAsal) {
                $querybuilder
                    ->andWhere('siswa.sekolahAsal = :sekolahAsal')
                    ->setParameter('sekolahAsal', $searchdata['sekolahAsal'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['referensi'] instanceof Referensi) {
                $querybuilder
                    ->andWhere('siswa.referensi = :referensi')
                    ->setParameter('referensi', $searchdata['referensi'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            }

            if ($searchdata['tertahkik'] === true) {
                $querybuilder
                    ->andWhere('siswa.calonSiswa = :calonSiswa')
                    ->setParameter('calonSiswa', !$searchdata['tertahkik'])
                ;

                $tampilkanTercari = true;
                $pencarianLanjutan = true;
            } else {
                $querybuilder
                    ->andWhere('siswa.calonSiswa = :calon')
                    ->setParameter('calon', true)
                ;
            }

            $pembandingBayar = $searchdata['pembandingBayar'];
            if ($searchdata['jumlahBayar'] != "") {
                if (array_key_exists('persenBayar', $searchdata) && $searchdata['persenBayar'] === true) {
                    $querybuilder
                        ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                        ->groupBy('siswa.id')
                    ;

                    if ($pembandingBayar == '<' || $pembandingBayar == '<=' || ($pembandingBayar == '=' && $searchdata['jumlahBayar'] == 0)) {
                        // masukkan pencarian untuk yg belum melakukan transaksi
                        $querybuilder
                            ->having("(SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                                ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar) "
                                ." OR SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) < 0")
                        ;
                    } else {
                        $querybuilder
                            ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar "
                               ." (SUM(DISTINCT(siswa.sisaBiayaPendaftaran)) + SUM(pembayaran.nominalTotalBiaya) - (SUM(pembayaran.nominalPotongan) + SUM(pembayaran.persenPotonganDinominalkan))) * :jumlahbayar")
                        ;
                    }

                    $querybuilder->setParameter('jumlahbayar', $searchdata['jumlahBayar'] / 100);
                } else {
                    $querybuilder
                        ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                        ->groupBy('siswa.id')
                        ->having("SUM(pembayaran.nominalTotalTransaksi) $pembandingBayar :jumlahbayar")
                        ->setParameter('jumlahbayar', $searchdata['jumlahBayar'])
                    ;
                }

                $tampilkanTercari = true;
            }
        } else {
            $pencarianLanjutan = true;
        }

        $pendaftarTercari = count($querybuilder->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 5);

        $idsiswa = '';
        foreach ($pagination->getItems() as $item) {
            $idsiswa .= $item->getId().':';
        }

        $tahkikform = $this->createForm('sisdik_tahkik', null, [
            'idsiswa' => $idsiswa,
        ]);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'tahkikform' => $tahkikform->createView(),
            'panitiaAktif' => $panitiaAktif,
            'pendaftarTotal' => $pendaftarTotal,
            'pendaftarTertahkik' => $pendaftarTertahkik,
            'pendaftarTercari' => $pendaftarTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'pencarianLanjutan' => $pencarianLanjutan,
            'searchkey' => $searchkey,
            'idsiswa' => $idsiswa,
        ];
    }

    /**
     * @Route("/{idsiswa}", name="tahkik_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaTahkik:index.html.twig")
     */
    public function tahkikAction(Request $request, $idsiswa = '')
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $tahkikform = $this->createForm('sisdik_tahkik', null, [
            'idsiswa' => $idsiswa,
        ]);
        $tahkikform->submit($request);

        if ($tahkikform->isValid()) {
            $data = $tahkikform->getData();

            $qbe = $em->createQueryBuilder();
            $querynomor = $em->createQueryBuilder()
                ->select($qbe->expr()->max('siswa.nomorUrutPersekolah'))
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->where('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah)
            ;

            $nomorUrutPersekolah = $querynomor->getQuery()->getSingleScalarResult();
            $nomorUrutPersekolah = $nomorUrutPersekolah === null ? 100000 : $nomorUrutPersekolah;

            $entities = $em->getRepository('LanggasSisdikBundle:Siswa')
                ->findBy([
                    'id' => preg_split('/:/', $idsiswa),
                ])
            ;
            foreach ($entities as $entity) {
                if (is_object($entity) && $entity instanceof Siswa) {
                    if (array_key_exists('siswa_'.$entity->getId(), $data) === true) {
                        if ($data['siswa_'.$entity->getId()] === true) {
                            $nomorUrutPersekolah++;
                            $entity->setNomorUrutPersekolah($nomorUrutPersekolah);
                            $entity->setNomorIndukSistem($nomorUrutPersekolah.$sekolah->getNomorUrut());
                            $entity->setCalonSiswa(false);

                            $em->persist($entity);
                        }
                    }
                }
            }
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.penahkikan.terbarui'))
            ;
        }

        return $this->redirect($this->generateUrl('tahkik'));
    }

    /**
     * Mencari panitia pendaftaran aktif
     *
     * Mengembalikan array berisi
     * index 0: daftar id panitia aktif,
     * index 1: id ketua panitia aktif,
     * index 2: id tahun panitia aktif,
     * index 3: string tahun panitia aktif.
     *
     * @return array panitiaaktif
     */
    private function getPanitiaAktif()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $entityPanitiaAktif = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->findOneBy([
            'sekolah' => $sekolah,
            'aktif' => 1,
        ]);

        $panitia = [];
        if (is_object($entityPanitiaAktif) && $entityPanitiaAktif instanceof PanitiaPendaftaran) {
            $panitia[0] = $entityPanitiaAktif->getPanitia();
            $panitia[1] = $entityPanitiaAktif->getKetuaPanitia()->getId();
            $panitia[2] = $entityPanitiaAktif->getTahun()->getId();
            $panitia[3] = $entityPanitiaAktif->getTahun()->getTahun();
        }

        return $panitia;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.tahkik', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
