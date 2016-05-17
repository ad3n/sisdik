<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/arsip-laporan-kehadiran-siswa")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN')")
 */
class ArsipLaporanKehadiranSiswaController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "arsip-laporan-kehadiran-siswa.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-kehadiran/";

    /**
     * @Route("/", name="arsip-laporan-kehadiran-siswa")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:arsip-laporan.html.twig")
     */
    public function indexAction()
    {
        $this->setCurrentMenu();
        $searchform = $this->createForm('sisdik_cari_arsiplaporankehadiransiswa');
        $hariIni = new \DateTime();
        $searchform->get('hinggaTanggal')->setData($hariIni);

        return [
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/lihat", name="arsip-laporan-kehadiran-siswa_lihat")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:arsip-laporan.html.twig")
     */
    public function lihatAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();
        $em = $this->getDoctrine()->getManager();

        $siswaKelas = $em->createQueryBuilder()
            ->select('siswaKelas, siswa, orangtuaWali')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->leftJoin('siswa.orangtuaWali', 'orangtuaWali')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->orderBy('siswa.nomorInduk', 'ASC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
        ;

        $kehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiranSiswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
            ->where('kehadiranSiswa.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform = $this->createForm('sisdik_cari_arsiplaporankehadiransiswa');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            if ($searchdata['kelas'] instanceof Kelas) {
                $siswaKelas
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;
            }

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $siswaKelas
                    ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;

                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal >= :dariTanggal AND kehadiranSiswa.tanggal <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d"))
                ;
            } else {
                $searchdata['hinggaTanggal'] = $hariIni = new \DateTime();
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hariIni->format("Y-m-d"))
                ;
            }

            if ($searchdata['searchkey'] != '') {
                $siswaKelas
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem = :searchkey2")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                    ->setParameter('searchkey2', $searchdata['searchkey'])
                ;
            }

            if ($searchdata['statusKehadiran'] != '') {
                $kehadiranSiswa
                    ->andWhere("kehadiranSiswa.statusKehadiran = :statusKehadiran")
                    ->setParameter('statusKehadiran', $searchdata['statusKehadiran'])
                ;
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('parameter.pencarian.laporan.tidak.boleh.kosong'))
            ;

            return $this->redirect($this->generateUrl('arsip-laporan-kehadiran-siswa'));
        }

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $daftarKehadiran = [];
        $daftarSiswaDiKelas = $siswaKelas->getQuery()->getResult();
        $kehadiranSiswaTotal = [];

        foreach ($daftarSiswaDiKelas as $siswaDiKelas) {
            $tmpKehadiran = [];
            if ($siswaDiKelas instanceof SiswaKelas) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.siswa = :siswa')
                    ->setParameter('siswa', $siswaDiKelas->getSiswa())
                ;
                $kehadiranPerSiswa = $kehadiranSiswa->getQuery()->getResult();

                if (count($kehadiranPerSiswa) > 0) {
                    foreach ($daftarStatusKehadiran as $key => $value) {
                        $tmpJumlahStatus = 0;
                        foreach ($kehadiranPerSiswa as $kehadiran) {
                            if ($kehadiran instanceof KehadiranSiswa) {
                                if ($key == $kehadiran->getStatusKehadiran()) {
                                    $tmpJumlahStatus++;
                                }
                            }
                        }
                        $tmpKehadiran[$key] = $tmpJumlahStatus;
                        $kehadiranSiswaTotal[$key] = isset($kehadiranSiswaTotal[$key]) ? $kehadiranSiswaTotal[$key] + $tmpJumlahStatus : $tmpJumlahStatus;
                    }

                    $tmpKehadiran['siswa'] = $siswaDiKelas->getSiswa();
                    $tmpKehadiran['kelasAktif'] = $siswaDiKelas->getAktif();
                    $tmpKehadiran['keteranganKelasAktif'] = $siswaDiKelas->getKeterangan();
                    $tmpKehadiran['jumlahHadir'] = $tmpKehadiran['a-hadir-tepat'] + $tmpKehadiran['b-hadir-telat'];
                    $tmpKehadiran['jumlahTidakHadir'] = $tmpKehadiran['c-alpa'] + $tmpKehadiran['d-izin'] + $tmpKehadiran['e-sakit'];
                    $daftarKehadiran[] = $tmpKehadiran;
                }
            }
        }

        return [
            'searchkey' => $searchdata['searchkey'],
            'searchform' => $searchform->createView(),
            'kelas' => $searchdata['kelas'],
            'tahunAkademik' => $searchdata['tahunAkademik'],
            'dariTanggal' => $searchdata['dariTanggal'],
            'hinggaTanggal' => $searchdata['hinggaTanggal'],
            'daftarStatusKehadiran' => $daftarStatusKehadiran,
            'kehadiranSiswa' => $daftarKehadiran,
            'kehadiranSiswaTotal' => $kehadiranSiswaTotal,
        ];
    }

    /**
     * @Route("/ekspor", name="arsip-laporan-kehadiran-siswa_ekspor")
     * @Method("POST")
     */
    public function eksporAction()
    {
        $sekolah = $this->getSekolah();
        $em = $this->getDoctrine()->getManager();

        $siswaKelas = $em->createQueryBuilder()
            ->select('siswaKelas, siswa, orangtuaWali')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->leftJoin('siswa.orangtuaWali', 'orangtuaWali')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->orderBy('siswa.nomorInduk', 'ASC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
        ;

        $kehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiranSiswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
            ->where('kehadiranSiswa.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform = $this->createForm('sisdik_cari_arsiplaporankehadiransiswa');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            if ($searchdata['kelas'] instanceof Kelas) {
                $siswaKelas
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;
            }

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $siswaKelas
                    ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;

                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal >= :dariTanggal AND kehadiranSiswa.tanggal <= :hinggaTanggal')
                    ->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"))
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"))
                ;
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d"))
                ;
            } else {
                $searchdata['hinggaTanggal'] = $hariIni = new \DateTime();
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal')
                    ->setParameter('hinggaTanggal', $hariIni->format("Y-m-d"))
                ;
            }

            if ($searchdata['searchkey'] != '') {
                $siswaKelas
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem = :searchkey2")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                    ->setParameter('searchkey2', $searchdata['searchkey'])
                ;
            }

            if ($searchdata['statusKehadiran'] != '') {
                $kehadiranSiswa
                    ->andWhere("kehadiranSiswa.statusKehadiran = :statusKehadiran")
                    ->setParameter('statusKehadiran', $searchdata['statusKehadiran'])
                ;
            }
        } else {
            $return = [
                "error" => $this->get('translator')->trans('parameter.pencarian.laporan.tidak.boleh.kosong'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $daftarKehadiran = [];
        $daftarSiswaDiKelas = $siswaKelas->getQuery()->getResult();

        foreach ($daftarSiswaDiKelas as $siswaDiKelas) {
            $tmpKehadiran = [];
            if ($siswaDiKelas instanceof SiswaKelas) {
                $kehadiranSiswa
                    ->andWhere('kehadiranSiswa.siswa = :siswa')
                    ->setParameter('siswa', $siswaDiKelas->getSiswa())
                ;
                $kehadiranPerSiswa = $kehadiranSiswa->getQuery()->getResult();

                if (count($kehadiranPerSiswa) > 0) {
                    foreach ($daftarStatusKehadiran as $key => $value) {
                        $tmpJumlahStatus = 0;
                        foreach ($kehadiranPerSiswa as $kehadiran) {
                            if ($kehadiran instanceof KehadiranSiswa) {
                                if ($key == $kehadiran->getStatusKehadiran()) {
                                    $tmpJumlahStatus++;
                                }
                            }
                        }
                        $tmpKehadiran[$key] = $tmpJumlahStatus;
                    }

                    $tmpKehadiran['siswa'] = $siswaDiKelas->getSiswa();
                    $tmpKehadiran['kelasAktif'] = $siswaDiKelas->getAktif();
                    $tmpKehadiran['keteranganKelasAktif'] = $siswaDiKelas->getKeterangan();
                    $tmpKehadiran['jumlahHadir'] = $tmpKehadiran['a-hadir-tepat'] + $tmpKehadiran['b-hadir-telat'];
                    $tmpKehadiran['jumlahTidakHadir'] = $tmpKehadiran['c-alpa'] + $tmpKehadiran['d-izin'] + $tmpKehadiran['e-sakit'];
                    $daftarKehadiran[] = $tmpKehadiran;
                }
            }
        }

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE."sisdik";
        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput.$extensionsource;
        $filetarget = $filenameoutput.$extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir.$sekolah->getId().'/')) {
            $fs->mkdir($outputdir.$sekolah->getId().'/');
        }

        $documentsource = $outputdir.$sekolah->getId().'/'.$filesource;
        $documenttarget = $outputdir.$sekolah->getId().'/'.$filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:KehadiranSiswa:styles.xml.twig"));
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:KehadiranSiswa:laporan.xml.twig", [
                        'searchkey' => $searchdata['searchkey'],
                        'kelas' => $searchdata['kelas'],
                        'tahunAkademik' => $searchdata['tahunAkademik'],
                        'dariTanggal' => $searchdata['dariTanggal'],
                        'hinggaTanggal' => $searchdata['hinggaTanggal'],
                        'daftarStatusKehadiran' => $daftarStatusKehadiran,
                        'kehadiranSiswa' => $daftarKehadiran,
                    ])
                );

                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("arsip-laporan-kehadiran-siswa_unduh", [
                            'filename' => $filetarget,
                        ]),
                        "filename" => $filetarget,
                    ];

                    $return = json_encode($return);

                    return new Response($return, 200, [
                        'Content-Type' => 'application/json',
                    ]);
                }
            }
        }

        $return = [
            "error" => $this->get('translator')->trans('errorinfo.tak.ada.kehadiran.siswa'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/unduh/{filename}/{type}", name="arsip-laporan-kehadiran-siswa_unduh")
     * @Method("GET")
     */
    public function unduhAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR.$sekolah->getId().'/'.$filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Pendaftaran');

        if ($type == 'ods') {
            $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        } elseif ($type == 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf');
        }

        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', filesize($documenttarget));

        return $response;
    }

    /**
     * @Route("/ubah-kelas", name="arsip-laporan-kehadiran-siswa_ubah-kelas")
     */
    public function ajaxUbahKelasAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $this->getRequest()->query->get('tahunAkademik');
        $kelas = $this->getRequest()->query->get('kelas');
        $tingkat = $this->getRequest()->query->get('tingkat');

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('kelas.tahunAkademik = :tahunAkademik')
            ->andWhere('kelas.tingkat = :tingkat')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
            ->setParameter('tingkat', $tingkat)
        ;
        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            $retval[] = [
                'optionValue' => $result->getId(),
                'optionDisplay' => $result->getNama(),
                'optionSelected' => $kelas == $result->getId() ? 'selected' : '',
            ];
        }

        $return = json_encode($retval);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', array(), 'navigations')][$this->get('translator')->trans('links.arsip.laporan.kehadiran.siswa', array(), 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
