<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PendidikanSiswa;
use Langgas\SisdikBundle\Entity\PenyakitSiswa;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 * @Route("/siswa-dalam-kelas")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS')")
 */
class SiswaDalamKelasController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "data-siswa-dalam-kelas.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/siswa-kelas/";

    /**
     * @Route("/", name="siswa_dalam_kelas")
     */
    public function indexAction()
    {
        /* @var $authorizationChecker AuthorizationCheckerInterface */
        $authorizationChecker = $this->container->get('security.authorization_checker');

        if ($authorizationChecker->isGranted([
            new Expression("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')"),
        ])) {
            return $this->redirect($this->generateUrl('siswa_dalam_kelas__admin'));
        } else {
            return $this->redirect($this->generateUrl('siswa_dalam_kelas__walikelas'));
        }
    }

    /**
     * @Route("/semua", name="siswa_dalam_kelas__admin")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH")
     */
    public function indexAdminAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carisiswadikelas');

        $querybuilder = $em->createQueryBuilder()
            ->select('siswaKelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswaKelas.aktif = :aktif')
            ->andWhere('siswa.calonSiswa = :calon')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('aktif', true)
            ->setParameter('calon', false)
        ;

        $tampilkanTercari = false;
        $siswaTotal = 0;
        $siswaTercari = 0;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder
                    ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }

            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $tampilkanTercari = true;
            }

            $siswaTotal = count($querybuilder->getQuery()->getResult());

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $siswaTercari = count($querybuilder->getQuery()->getResult());
        }

        if ($this->getRequest()->query->get('sort') == '') {
            $querybuilder
                ->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('siswa.namaLengkap', 'ASC')
            ;
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 100);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'searchdata' => $searchdata,
            'siswaTotal' => $siswaTotal,
            'tampilkanTercari' => $tampilkanTercari,
            'siswaTercari' => $siswaTercari,
        ];
    }

    /**
     * @Route("/spesifik", name="siswa_dalam_kelas__walikelas")
     * @Template()
     * @Secure(roles="ROLE_WALI_KELAS")
     */
    public function indexWaliKelasAction()
    {
        $sekolah = $this->getSekolah();

        /* @var $user User */
        $user = $this->getUser();

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        if (!$tahunAkademikAktif instanceof TahunAkademik) {
            throw $this->createNotFoundException($this->container->get('translator')->trans('tidak.ada.tahun.akademik.aktif'));
        }

        $waliKelasAktif = $em->getRepository('LanggasSisdikBundle:WaliKelas')
            ->findBy([
                'tahunAkademik' => $tahunAkademikAktif,
                'user' => $user,
            ])
        ;

        if (count($waliKelasAktif) <= 0) {
            throw $this->createNotFoundException($this->container->get('translator')->trans('user.bukan.wali.kelas.di.tahun.akademik.aktif', [
                '%tahun-akademik%' => $tahunAkademikAktif->getNama(),
            ]));
        }

        $daftarKelas = [];
        foreach ($waliKelasAktif as $waliKelas) {
            /* @var $waliKelas WaliKelas */
            $daftarKelas[] = $waliKelas->getKelas()->getId();
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('siswaKelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
            ->andWhere('siswaKelas.aktif = :aktif')
            ->andWhere('siswa.calonSiswa = :calon')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademikAktif)
            ->setParameter('aktif', true)
            ->setParameter('calon', false)
        ;

        $tampilkanTercari = false;
        $siswaTotal = 0;
        $siswaTercari = 0;

        $searchform = $this->createForm('sisdik_carisiswadikelas', null, [
            'mode_wali_kelas' => true,
            'kelas_id' => $daftarKelas,
        ]);

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $tampilkanTercari = true;
            }

            $siswaTotal = count($querybuilder->getQuery()->getResult());

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $siswaTercari = count($querybuilder->getQuery()->getResult());
        }

        if ($this->getRequest()->query->get('sort') == '') {
            $querybuilder
                ->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('siswa.namaLengkap', 'ASC')
            ;
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 100);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'searchdata' => $searchdata,
            'siswaTotal' => $siswaTotal,
            'tampilkanTercari' => $tampilkanTercari,
            'siswaTercari' => $siswaTercari,
            'tahunAkademik' => $tahunAkademikAktif,
        ];
    }

    /**
     * @Route("/profil-{id}", name="siswa_dalam_kelas__profil")
     * @Method("GET")
     * @Template()
     */
    public function profilAction($id)
    {
        $sekolah = $this->getSekolah();

        /* @var $user User */
        $user = $this->getUser();

        $authorizationChecker = $this->container->get('security.authorization_checker');

        $isWaliKelas = false;
        if ($authorizationChecker->isGranted([
            new Expression("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')"),
        ])) {
            $isWaliKelas = false;
        } elseif ($authorizationChecker->isGranted([
            new Expression("hasRole('ROLE_WALI_KELAS')"),
        ])) {
            $isWaliKelas = true;
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        /* @var $siswa Siswa */
        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);
        if (!$siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $dalamPerwalian = false;

        if ($isWaliKelas) {
            $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'aktif' => true,
                ])
            ;

            if (!$tahunAkademikAktif instanceof TahunAkademik) {
                throw $this->createNotFoundException($this->container->get('translator')->trans('tidak.ada.tahun.akademik.aktif'));
            }

            $waliKelasAktif = $em->getRepository('LanggasSisdikBundle:WaliKelas')
                ->findBy([
                    'tahunAkademik' => $tahunAkademikAktif,
                    'user' => $user,
                ])
            ;

            foreach ($waliKelasAktif as $waliKelas) {
                if (!$waliKelas instanceof WaliKelas) {
                    throw $this->createNotFoundException($this->container->get('translator')->trans('user.bukan.wali.kelas.di.tahun.akademik.aktif', [
                        '%tahun-akademik%' => $tahunAkademikAktif->getNama(),
                    ]));
                }

                $siswaDalamPerwalian = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
                    ->findOneBy([
                        'tahunAkademik' => $tahunAkademikAktif,
                        'kelas' => $waliKelas->getKelas(),
                        'siswa' => $siswa,
                        'aktif' => true,
                    ])
                ;

                if ($siswaDalamPerwalian instanceof SiswaKelas) {
                    $dalamPerwalian = true;
                    break;
                }
            }

            if (!$dalamPerwalian) {
                throw $this->createNotFoundException($this->container->get('translator')->trans('siswa.bukan.dalam.perwalian'));
            }
        }

        $siswaKelas = $em->createQueryBuilder()
            ->select('siswakelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswakelas')
            ->leftJoin('siswakelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswakelas.kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('siswakelas.siswa = :siswa')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('tahunAkademik.nama', 'DESC')
            ->addOrderBy('tingkat.urutan', 'DESC')
            ->addOrderBy('kelas.urutan', 'ASC')
            ->addOrderBy('siswakelas.aktif', 'DESC')
            ->setParameter('siswa', $siswa)
            ->getQuery()
            ->getResult()
        ;

        $orangtuaWali = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
            ->findBy([
                'siswa' => $siswa,
            ], [
                'aktif' => 'DESC',
            ])
        ;

        $dokumenSiswa = $em->createQueryBuilder()
            ->select('dokumenSiswa')
            ->from('LanggasSisdikBundle:DokumenSiswa', 'dokumenSiswa')
            ->leftJoin('dokumenSiswa.jenisDokumenSiswa', 'jenisDokumenSiswa')
            ->where('dokumenSiswa.siswa = :siswa')
            ->orderBy('jenisDokumenSiswa.urutan', 'ASC')
            ->setParameter('siswa', $siswa)
            ->getQuery()
            ->getResult()
        ;

        $pendidikanSiswa = $em->getRepository('LanggasSisdikBundle:PendidikanSiswa')
            ->findBy([
                'siswa' => $siswa,
            ], [
                'jenjang' => 'DESC',
            ])
        ;

        $penyakitSiswa = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')
            ->findBy([
                'siswa' => $siswa,
            ])
        ;

        return [
            'siswa' => $siswa,
            'siswaKelas' => $siswaKelas,
            'orangtuaWali' => $orangtuaWali,
            'dokumenSiswa' => $dokumenSiswa,
            'pendidikanSiswa' => $pendidikanSiswa,
            'penyakitSiswa' => $penyakitSiswa,
        ];
    }

    /**
     * @Route("/ekspor-admin", name="siswa_dalam_kelas__eksporadmin")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH")
     */
    public function eksporAdminAction()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carisiswadikelas');

        $querybuilder = $em->createQueryBuilder()
            ->select('siswaKelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswaKelas.aktif = :aktif')
            ->andWhere('siswa.calonSiswa = :calon')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('aktif', true)
            ->setParameter('calon', false)
        ;

        $siswaTotal = 0;
        $siswaTercari = 0;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder
                    ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }

            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;
            }

            $siswaTotal = count($querybuilder->getQuery()->getResult());

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;
            }

            $siswaTercari = count($querybuilder->getQuery()->getResult());
        }

        $dataSiswa = $querybuilder->getQuery()->getResult();

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $patterns = ['/\s+/', '/\//'];
        $replacements = ['', '_'];
        $filenameoutput = self::OUTPUTFILE
            .preg_replace($patterns, $replacements, $searchdata['tahunAkademik']->getNama())
            .'.'
            .preg_replace($patterns, $replacements, $searchdata['kelas']->getNama())
            .".sisdik"
        ;

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
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:SiswaDalamKelas:siswadikelas.xml.twig", [
                        'siswaTotal' => $siswaTotal,
                        'siswaTercari' => $siswaTercari,
                        'dataSiswa' => $dataSiswa,
                        'tahunAkademik' => $searchdata['tahunAkademik'],
                        'kelas' => $searchdata['kelas'],
                        'searchkey' => $searchdata['searchkey'],
                    ])
                );

                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("siswa_dalam_kelas__unduh", [
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
            "error" => $this->get('translator')->trans('ada.kesalahan.di.server'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/ekspor-spesifik", name="siswa_dalam_kelas__eksporspesifik")
     * @Method("POST")
     * @Secure(roles="ROLE_WALI_KELAS")
     */
    public function eksporSpesifikAction()
    {
        $sekolah = $this->getSekolah();

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademikAktif = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        if (!$tahunAkademikAktif instanceof TahunAkademik) {
            $return = [
                "error" => $this->get('translator')->trans('tidak.ada.tahun.akademik.aktif'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $waliKelasAktif = $em->getRepository('LanggasSisdikBundle:WaliKelas')
            ->findBy([
                'tahunAkademik' => $tahunAkademikAktif,
                'user' => $user,
            ])
        ;

        if (count($waliKelasAktif) <= 0) {
            $return = [
                "error" => $this->container->get('translator')->trans('user.bukan.wali.kelas.di.tahun.akademik.aktif', [
                    '%tahun-akademik%' => $tahunAkademikAktif->getNama(),
                ]),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $daftarKelas = [];
        foreach ($waliKelasAktif as $waliKelas) {
            $daftarKelas[] = $waliKelas->getKelas()->getId();
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('siswaKelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
            ->leftJoin('siswaKelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswaKelas.siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswaKelas.tahunAkademik = :tahunAkademik')
            ->andWhere('siswaKelas.aktif = :aktif')
            ->andWhere('siswa.calonSiswa = :calon')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademikAktif)
            ->setParameter('aktif', true)
            ->setParameter('calon', false)
        ;

        $siswaTotal = 0;
        $siswaTercari = 0;

        $searchform = $this->createForm('sisdik_carisiswadikelas', null, [
            'mode_wali_kelas' => true,
            'kelas_id' => $daftarKelas,
        ]);

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->setParameter('kelas', $searchdata['kelas'])
                ;

                $tampilkanTercari = true;
            }

            $siswaTotal = count($querybuilder->getQuery()->getResult());

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }

            $siswaTercari = count($querybuilder->getQuery()->getResult());
        }

        $dataSiswa = $querybuilder->getQuery()->getResult();

        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $patterns = ['/\s+/', '/\//'];
        $replacements = ['', '_'];
        $filenameoutput = self::OUTPUTFILE
            .preg_replace($patterns, $replacements, $tahunAkademikAktif->getNama())
            .'.'
            .preg_replace($patterns, $replacements, $searchdata['kelas']->getNama())
            .".sisdik"
        ;

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
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:SiswaDalamKelas:siswadikelas.xml.twig", [
                        'siswaTotal' => $siswaTotal,
                        'siswaTercari' => $siswaTercari,
                        'dataSiswa' => $dataSiswa,
                        'tahunAkademik' => $tahunAkademikAktif,
                        'kelas' => $searchdata['kelas'],
                        'searchkey' => $searchdata['searchkey'],
                    ])
                );

                if ($ziparchive->close() === true) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("siswa_dalam_kelas__unduh", [
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
            "error" => $this->get('translator')->trans('ada.kesalahan.di.server'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/unduh/{filename}/{type}", name="siswa_dalam_kelas__unduh")
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
        $response->headers->set('Content-Description', 'Data Siswa Dalam Kelas');

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

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.academic', [], 'navigations')][$this->get('translator')->trans('links.siswa.di.kelas', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
