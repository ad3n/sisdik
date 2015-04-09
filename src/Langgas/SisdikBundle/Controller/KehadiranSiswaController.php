<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Langgas\SisdikBundle\Entity\Tingkat;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Langgas\SisdikBundle\Entity\Templatesms;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\TranslationBundle\Annotation\Ignore;

/**
 * @Route("/kehadiran-siswa")
 * @PreAuthorize("hasAnyRole('ROLE_GURU_PIKET', 'ROLE_GURU')")
 */
class KehadiranSiswaController extends Controller
{
    const TMP_DIR = "/tmp";

    /**
     * @Route("/", name="kehadiran-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kehadiransiswasearch');
        $formhapus = $this->createForm('sisdik_kehadiransiswahapus');

        $hariIni = new \DateTime();
        $searchform->get('tanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah,
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        $mesinWakil = $em->getRepository('LanggasSisdikBundle:MesinWakil')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        return [
            'searchform' => $searchform->createView(),
            'formhapus' => $formhapus->createView(),
            'tahunAkademik' => $tahunAkademik,
            'mesinWakil' => $mesinWakil,
        ];
    }

    /**
     * @Route("/", name="kehadiran-siswa_hapus")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:index.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function hapusAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        /* @var $translator Translator */
        $translator = $this->get('translator');

        $searchform = $this->createForm('sisdik_kehadiransiswasearch');
        $formhapus = $this->createForm('sisdik_kehadiransiswahapus');

        $hariIni = new \DateTime();
        $searchform->get('tanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'sekolah' => $sekolah,
                'aktif' => true,
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        $mesinWakil = $em->getRepository('LanggasSisdikBundle:MesinWakil')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        $formhapus->submit($this->getRequest());

        $formhapusInvalid = 0;
        if ($formhapus->isValid()) {
            $tanggal = $formhapus->get('tanggal')->getData();
            $qbHapusKehadiran = $em->createQueryBuilder()
                ->delete('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                ->where('kehadiran.sekolah = :sekolah')
                ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
                ->andWhere('kehadiran.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('tanggal', $tanggal)
            ;

            $qbHapusProsesKehadiran = $em->createQueryBuilder()
                ->delete('LanggasSisdikBundle:ProsesKehadiranSiswa', 'proses')
                ->where('proses.sekolah = :sekolah')
                ->andWhere('proses.tahunAkademik = :tahunAkademik')
                ->andWhere('proses.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('tanggal', $tanggal)
            ;

            $qbHapusKepulangan = $em->createQueryBuilder()
                ->delete('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                ->where('kepulangan.sekolah = :sekolah')
                ->andWhere('kepulangan.tahunAkademik = :tahunAkademik')
                ->andWhere('kepulangan.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('tanggal', $tanggal)
            ;

            $qbHapusProsesKepulangan = $em->createQueryBuilder()
                ->delete('LanggasSisdikBundle:ProsesKepulanganSiswa', 'proses')
                ->where('proses.sekolah = :sekolah')
                ->andWhere('proses.tahunAkademik = :tahunAkademik')
                ->andWhere('proses.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('tanggal', $tanggal)
            ;

            $kelas = $formhapus->get('kelas')->getData();
            if ($kelas instanceof Kelas) {
                $qbHapusKehadiran
                    ->andWhere('kehadiran.kelas = :kelas')
                    ->setParameter('kelas', $kelas)
                ;

                $qbHapusProsesKehadiran
                    ->andWhere('proses.kelas = :kelas')
                    ->setParameter('kelas', $kelas)
                ;

                $qbHapusKepulangan
                    ->andWhere('kepulangan.kelas = :kelas')
                    ->setParameter('kelas', $kelas)
                ;

                $qbHapusProsesKepulangan
                    ->andWhere('proses.kelas = :kelas')
                    ->setParameter('kelas', $kelas)
                ;
            }

            $qbHapusKehadiran->getQuery()->execute();
            $qbHapusProsesKehadiran->getQuery()->execute();

            $qbHapusKepulangan->getQuery()->execute();
            $qbHapusProsesKepulangan->getQuery()->execute();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $translator->trans('flash.kehadiran.siswa.berhasil.dihapus', [
                    '%tanggal%' => $tanggal->format('d/m/Y'),
                    '%kelas%' => $kelas instanceof Kelas ? $kelas->getNama() : $translator->trans('seluruh.kelas'),
                ]))
            ;

            return $this->redirect($this->generateUrl('kehadiran-siswa'));
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $translator->trans('flash.kehadiran.siswa.gagal.dihapus'))
            ;

            $formhapusInvalid = 1;
        }

        return [
            'searchform' => $searchform->createView(),
            'formhapus' => $formhapus->createView(),
            'tahunAkademik' => $tahunAkademik,
            'mesinWakil' => $mesinWakil,
            'formhapusInvalid' => $formhapusInvalid,
        ];
    }

    /**
     * @Route("/edit", name="kehadiran-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kehadiransiswasearch');

        $querybuilder = $em->createQueryBuilder()
            ->select('kehadiran, siswa, orangtuaWali')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->leftJoin('siswa.orangtuaWali', 'orangtuaWali')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        $buildparam = null;
        $kelas = null;

        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $kbmAktif = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
                ->findOneBy([
                    'kbm' => true,
                    'sekolah' => $sekolah,
                    'tanggal' => $searchdata['tanggal'],
                ])
            ;

            if (!(is_object($kbmAktif) && $kbmAktif instanceof KalenderPendidikan)) {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.kehadiran.siswa.bukan.hari.kbm.aktif'))
                ;

                return $this->redirect($this->generateUrl('kehadiran-siswa'));
            }

            if ($searchdata['tanggal'] instanceof \DateTime) {
                $querybuilder->andWhere('kehadiran.tanggal = :tanggal');
                $querybuilder->setParameter('tanggal', $searchdata['tanggal']);

                $buildparam['tanggal'] = $searchdata['tanggal']->format('Y-m-d');
            } else {
                $buildparam['tanggal'] = '';
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem = :searchkey2");
                $querybuilder->setParameter('searchkey', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('searchkey2', $searchdata['searchkey']);

                $buildparam['searchkey'] = $searchdata['searchkey'];
            } else {
                $buildparam['searchkey'] = '';
            }

            if ($searchdata['tingkat'] instanceof Tingkat) {
                $querybuilder->andWhere("kelas.tingkat = :tingkat");
                $querybuilder->setParameter('tingkat', $searchdata['tingkat']);

                $buildparam['tingkat'] = $searchdata['tingkat']->getId();
            } else {
                $buildparam['tingkat'] = '';
            }

            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder->andWhere("kelas.id = :kelas");
                $querybuilder->setParameter('kelas', $searchdata['kelas']);

                $kelas = $em->getRepository('LanggasSisdikBundle:Kelas')->find($searchdata['kelas']->getId());

                $buildparam['kelas'] = $searchdata['kelas']->getId();
            } else {
                $buildparam['kelas'] = '';
            }

            if ($searchdata['statusKehadiran'] != '') {
                $querybuilder->andWhere("kehadiran.statusKehadiran = :statusKehadiran");
                $querybuilder->setParameter('statusKehadiran', $searchdata['statusKehadiran']);

                $buildparam['statusKehadiran'] = $searchdata['statusKehadiran'];
            } else {
                $buildparam['statusKehadiran'] = '';
            }

            $entities = $querybuilder->getQuery()->getResult();

            $students = $this->createForm('sisdik_kehadiransiswa', null, ['buildparam' => $buildparam]);

            $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                ->findOneBy([
                    'aktif' => true,
                    'sekolah' => $sekolah,
                ])
            ;

            $prosesKehadiranSiswa = null;
            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'tanggal' => $searchdata['tanggal'],
                ])
            ;

            $formInisiasi = $this->createForm('sisdik_kehadiransiswainisiasi', null, [
                'kelas' => $kelas,
                'tanggal' => $searchdata['tanggal']->format('Y-m-d'),
            ]);

            $formSms = $this->createForm('sisdik_kehadiransiswasms', null, [
                'kelas' => $kelas,
                'tanggal' => $searchdata['tanggal']->format('Y-m-d'),
                'kehadiran' => $entities,
            ]);

            $waliKelas = $em->getRepository('LanggasSisdikBundle:WaliKelas')
                ->findOneBy([
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                ])
            ;
            $formSmsRingkasan = null;
            if ($waliKelas instanceof WaliKelas) {
                $formSmsRingkasan = $this->createForm('sisdik_kehadiransiswasms_ringkasan', null, [
                    'wali_kelas' => $waliKelas,
                    'tanggal' => $searchdata['tanggal']->format('Y-m-d'),
                ]);
            }

            return [
                'kelas' => $kelas,
                'waliKelas' => $waliKelas,
                'entities' => $entities,
                'form' => $students->createView(),
                'searchform' => $searchform->createView(),
                'buildparam' => $buildparam,
                'tahunAkademik' => $tahunAkademik,
                'prosesKehadiranSiswa' => $prosesKehadiranSiswa,
                'tanggal' => $searchdata['tanggal'],
                'formInisiasi' => $formInisiasi->createView(),
                'formSms' => $formSms->createView(),
                'formSmsRingkasan' => ($formSmsRingkasan instanceof Form) ? $formSmsRingkasan->createView() : $formSmsRingkasan,
            ];
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.kehadiran.siswa.pencarian.gagal'))
            ;

            return $this->redirect($this->generateUrl('kehadiran-siswa'));
        }
    }

    /**
     * Memperbarui kehadiran siswa.
     *
     * @Route("/update", name="kehadiran-siswa_update")
     * @Method("POST")
     * @Secure(roles="ROLE_GURU_PIKET")
     */
    public function updateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->get('sisdik_kehadiransiswa');

        foreach ($data as $keys => $values) {
            if (preg_match('/kehadiran_(\d+)$/', $keys, $matches) !== false) {
                if (array_key_exists(1, $matches)) {
                    $kehadiran = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')->find($matches[1]);
                    if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                        $kehadiran->setStatusKehadiran($values);
                        $kehadiran->setPermulaan(false);
                        $kehadiran->setTervalidasi(true);
                        $kehadiran->setKeteranganStatus($data['kehadiran_keterangan_'.$matches[1]]);
                        $em->persist($kehadiran);
                    }
                }
            }
        }

        $return = [];
        if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                ->findOneBy([
                    'sekolah' => $kehadiran->getSekolah(),
                    'tahunAkademik' => $kehadiran->getTahunAkademik(),
                    'kelas' => $kehadiran->getKelas(),
                    'tanggal' => $kehadiran->getTanggal(),
                ])
            ;

            if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                $prosesKehadiranSiswa->setBerhasilValidasi(true);
            } else {
                $prosesKehadiranSiswa = new ProsesKehadiranSiswa();
                $prosesKehadiranSiswa->setSekolah($kehadiran->getSekolah());
                $prosesKehadiranSiswa->setTahunAkademik($kehadiran->getTahunAkademik());
                $prosesKehadiranSiswa->setKelas($kehadiran->getKelas());
                $prosesKehadiranSiswa->setTanggal($kehadiran->getTanggal());
                $prosesKehadiranSiswa->setBerhasilValidasi(true);
            }

            $em->persist($prosesKehadiranSiswa);
            $return['berhasilValidasi'] = 1;
        }

        $em->flush();

        $return['responseCode'] = 200;
        $return['responseText'] = $this->get('translator')->trans('flash.presence.student.updated');
        $return['matches'] = $matches;
        $return['data'] = $data;

        $return = json_encode($return);

        return new Response($return, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Menginisiasi kehadiran siswa.
     *
     * @Route("/inisiasi/{kelas_id}/{tanggal}", name="kehadiran-siswa_inisiasi")
     * @Method("POST")
     * @Secure(roles="ROLE_GURU_PIKET")
     */
    public function inisiasiAction($kelas_id, $tanggal)
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah,
            ])
        ;

        $kelas = $em->getRepository('LanggasSisdikBundle:Kelas')->find($kelas_id);

        $formInisiasi = $this->createForm('sisdik_kehadiransiswainisiasi', null, [
                'kelas' => $kelas,
                'tanggal' => $tanggal,
            ])
            ->submit($this->getRequest())
        ;

        if ($formInisiasi->isValid()) {
            $statusKehadiran = $formInisiasi->get('statusKehadiran')->getData();

            $qbKehadiran = $em->createQueryBuilder()
                ->select('kehadiran')
                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                ->where('kehadiran.sekolah = :sekolah')
                ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
                ->andWhere('kehadiran.kelas = :kelas')
                ->andWhere('kehadiran.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('kelas', $kelas)
                ->setParameter('tanggal', $tanggal)
            ;
            $entities = $qbKehadiran->getQuery()->getResult();

            if (count($entities) > 0) {
                foreach ($entities as $kehadiran) {
                    if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                        $kehadiran->setKeteranganStatus(null);
                        $kehadiran->setPermulaan(true);
                        $kehadiran->setTervalidasi(false);
                        $kehadiran->setSmsDlr(null);
                        $kehadiran->setSmsDlrtime(null);
                        $kehadiran->setSmsTerproses(false);
                        $kehadiran->setJam(null);
                        $kehadiran->setStatusKehadiran($statusKehadiran);

                        $em->persist($kehadiran);
                    }
                }
            } else {
                $qbSiswaKelas = $em->createQueryBuilder()
                    ->select('siswaKelas')
                    ->from('LanggasSisdikBundle:SiswaKelas', 'siswaKelas')
                    ->where('siswaKelas.tahunAkademik = :tahunakademik')
                    ->andWhere('siswaKelas.kelas = :kelas')
                    ->andWhere('siswaKelas.aktif = :aktif')
                    ->setParameter('tahunakademik', $tahunAkademik)
                    ->setParameter('kelas', $kelas)
                    ->setParameter('aktif', true)
                ;
                $entitiesSiswaKelas = $qbSiswaKelas->getQuery()->getResult();

                foreach ($entitiesSiswaKelas as $siswaKelas) {
                    if (!(is_object($siswaKelas) && $siswaKelas instanceof SiswaKelas)) {
                        continue;
                    }

                    $qbKehadiran = $em->createQueryBuilder()
                        ->select('kehadiran')
                        ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                        ->where('kehadiran.sekolah = :sekolah')
                        ->andWhere('kehadiran.siswa = :siswa')
                        ->andWhere('kehadiran.tanggal = :tanggal')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('siswa', $siswaKelas->getSiswa())
                        ->setParameter('tanggal', $tanggal)
                    ;
                    $entityKehadiran = $qbKehadiran->getQuery()->getResult();

                    if (count($entityKehadiran) >= 1) {
                        continue;
                    }

                    $kehadiran = new KehadiranSiswa();
                    $kehadiran->setSekolah($sekolah);
                    $kehadiran->setTahunAkademik($tahunAkademik);
                    $kehadiran->setKelas($kelas);
                    $kehadiran->setSiswa($siswaKelas->getSiswa());
                    $kehadiran->setStatusKehadiran($statusKehadiran);
                    $kehadiran->setPermulaan(true);
                    $kehadiran->setTervalidasi(false);
                    $kehadiran->setTanggal(new \DateTime($tanggal));
                    $kehadiran->setSmsTerproses(false);

                    $em->persist($kehadiran);
                }
            }

            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'tanggal' => new \DateTime($tanggal),
                ])
            ;
            if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                $prosesKehadiranSiswa->setBerhasilInisiasi(true);
            } else {
                $prosesKehadiranSiswa = new ProsesKehadiranSiswa();
                $prosesKehadiranSiswa->setSekolah($sekolah);
                $prosesKehadiranSiswa->setTahunAkademik($tahunAkademik);
                $prosesKehadiranSiswa->setKelas($kelas);
                $prosesKehadiranSiswa->setTanggal(new \DateTime($tanggal));
                $prosesKehadiranSiswa->setBerhasilInisiasi(true);
            }

            $em->persist($prosesKehadiranSiswa);

            $em->flush();

            $return = [
                "responseCode" => 200,
                "responseText" => $this->get('translator')->trans('flash.inisiasi.berhasil.dijalankan'),
                "data" => 'refresh',
            ];
        } else {
            $return = [
                "responseCode" => 400,
                "responseText" => $this->get('translator')->trans('flash.inisiasi.gagal.dijalankan'),
                "data" => 'norefresh',
            ];
        }

        $return = json_encode($return);

        return new Response($return, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Mengirim SMS kehadiran.
     *
     * @Route("/kirim-sms/{kelas_id}/{tanggal}", name="kehadiran-siswa_kirimsms")
     * @Method("POST")
     * @Secure(roles="ROLE_GURU_PIKET")
     */
    public function kirimSmsAction($kelas_id, $tanggal)
    {
        $sekolah = $this->getSekolah();
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        /* @var $translator Translator */
        $translator = $this->get('translator');
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $tanggalTerpilih = new \DateTime($tanggal);
        $mingguanHariKe = $tanggalTerpilih->format('N');
        $bulananHariKe = $tanggalTerpilih->format('j');

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah,
            ])
        ;

        $kelas = $em->getRepository('LanggasSisdikBundle:Kelas')->find($kelas_id);

        $qbKehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiran')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->where('kehadiran.sekolah = :sekolah')
            ->andWhere('kehadiran.kelas = :kelas')
            ->andWhere('kehadiran.tanggal = :tanggal')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('kelas', $kelas)
            ->setParameter('tanggal', $tanggalTerpilih->format('Y-m-d'))
        ;
        $kehadiranSiswa = $qbKehadiranSiswa->getQuery()->getResult();

        $formKirimSms = $this->createForm('sisdik_kehadiransiswasms', null, [
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'kehadiran' => $kehadiranSiswa,
        ]);
        $formKirimSms->submit($this->getRequest());

        if ($formKirimSms->isValid()) {
            $statusKehadiran = $formKirimSms->get('statusKehadiran')->getData();
            $siswa = $formKirimSms->get('siswa')->getData();

            $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                ->findOneBy([
                    'sekolah' => $sekolah,
                ])
            ;

            // PERINGATAN: diasumsikan bahwa perulangan apapun
            // menggunakan template sms yang serupa :(
            $jadwalKehadiran = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'kirimSms' => true,
                    'statusKehadiran' => $statusKehadiran,
                ])
            ;
            if (!($jadwalKehadiran instanceof JadwalKehadiran)) {
                $return['responseCode'] = 400;
                $return['responseText'] = "tidak ada jadwal yang sesuai atau jadwal tidak diatur untuk bisa mengirim sms";
                $return = json_encode($return);

                return new Response($return, 200, ['Content-Type' => 'application/json']);
            }

            switch ($jadwalKehadiran->getStatusKehadiran()) {
                case 'a-hadir-tepat':
                    $jenisLayananSms = 'l-kehadiran-tepat';
                    break;
                case 'b-hadir-telat':
                    $jenisLayananSms = 'm-kehadiran-telat';
                    break;
                case 'c-alpa':
                    $jenisLayananSms = 'k-kehadiran-alpa';
                    break;
                case 'd-izin':
                    $jenisLayananSms = 'n-kehadiran-izin';
                    break;
                case 'e-sakit':
                    $jenisLayananSms = 'o-kehadiran-sakit';
                    break;
            }

            $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'jenisLayanan' => $jenisLayananSms,
                    'status' => true,
                ])
            ;
            if (!(is_object($layananSms) && $layananSms instanceof PilihanLayananSms)) {
                $return['responseCode'] = 400;
                $return['responseText'] = "layanan sms tidak aktif atau tidak tersedia";
                $return = json_encode($return);

                return new Response($return, 200, ['Content-Type' => 'application/json']);
            }

            if ($siswa instanceof Siswa) {
                $kehadiran = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'tahunAkademik' => $tahunAkademik,
                        'kelas' => $kelas,
                        'siswa' => $siswa,
                        'statusKehadiran' => $statusKehadiran,
                        'tanggal' => $tanggalTerpilih,
                    ])
                ;
                if (!(is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa)) {
                    $return['responseCode'] = 400;
                    $return['responseText'] = "kehadiran siswa yang terpilih tak ditemukan";
                    $return = json_encode($return);

                    return new Response($return, 200, ['Content-Type' => 'application/json']);
                }

                $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                    ->findOneBy([
                        'siswa' => $siswa,
                        'aktif' => true,
                    ])
                ;
                if ((is_object($ortuWaliAktif) && $ortuWaliAktif instanceof OrangtuaWali)) {
                    $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                    if ($ponselOrtuWaliAktif != "") {
                        $tekstemplate = $jadwalKehadiran->getTemplatesms()->getTeks();
                        $tekstemplate = str_replace("%nama%", $kehadiran->getSiswa()->getNamaLengkap(), $tekstemplate);
                        $tekstemplate = str_replace("%nis%", $kehadiran->getSiswa()->getNomorInduk(), $tekstemplate);
                        $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$mingguanHariKe]), $tekstemplate);
                        $tekstemplate = str_replace("%tanggal%", $tanggalTerpilih->format('d/m/Y'), $tekstemplate);
                        $tekstemplate = str_replace("%jam%", $kehadiran->getJam(), $tekstemplate);
                        $tekstemplate = str_replace("%keterangan%", $kehadiran->getKeteranganStatus(), $tekstemplate);

                        $terkirim = false;
                        $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWaliAktif);
                        foreach ($nomorponsel as $ponsel) {
                            $messenger = $this->get('sisdik.messenger');
                            if ($messenger instanceof Messenger) {
                                if ($vendorSekolah instanceof VendorSekolah) {
                                    if ($vendorSekolah->getJenis() == 'khusus') {
                                        $messenger->setUseVendor(true);
                                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                    }
                                }
                                $messenger->setPhoneNumber($ponsel);
                                $messenger->setMessage($tekstemplate);
                                $messenger->sendMessage($sekolah);
                                $terkirim = true;
                            }
                        }

                        if ($terkirim) {
                            $kehadiran->setSmsTerproses($terkirim);
                            $em->persist($kehadiran);
                        }
                    } else {
                        $return['responseCode'] = 400;
                        $return['responseText'] = "Nomor ponsel orangtua/wali tak tersedia";
                        $return = json_encode($return);

                        return new Response($return, 200, ['Content-Type' => 'application/json']);
                    }
                } else {
                    $return['responseCode'] = 400;
                    $return['responseText'] = "Orang tua/wali siswa tak ditemukan";
                    $return = json_encode($return);

                    return new Response($return, 200, ['Content-Type' => 'application/json']);
                }
            } else {
                $qbKehadiranSiswa
                    ->andWhere('kehadiran.statusKehadiran = :status')
                    ->setParameter('status', $statusKehadiran)
                ;
                $kehadiranSiswaPerStatus = $qbKehadiranSiswa->getQuery()->getResult();

                foreach ($kehadiranSiswaPerStatus as $kehadiran) {
                    if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                        $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                            ->findOneBy([
                                'siswa' => $kehadiran->getSiswa(),
                                'aktif' => true,
                            ])
                        ;
                        if ((is_object($ortuWaliAktif) && $ortuWaliAktif instanceof OrangtuaWali)) {
                            $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                            if ($ponselOrtuWaliAktif != "") {
                                $tekstemplate = $jadwalKehadiran->getTemplatesms()->getTeks();
                                $tekstemplate = str_replace("%nama%", $kehadiran->getSiswa()->getNamaLengkap(), $tekstemplate);
                                $tekstemplate = str_replace("%nis%", $kehadiran->getSiswa()->getNomorInduk(), $tekstemplate);
                                $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$mingguanHariKe]), $tekstemplate);
                                $tekstemplate = str_replace("%tanggal%", $tanggalTerpilih->format('d/m/Y'), $tekstemplate);
                                $tekstemplate = str_replace("%jam%", $kehadiran->getJam(), $tekstemplate);
                                $tekstemplate = str_replace("%keterangan%", $kehadiran->getKeteranganStatus(), $tekstemplate);

                                $terkirim = false;
                                $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWaliAktif);
                                foreach ($nomorponsel as $ponsel) {
                                    $messenger = $this->get('sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
                                        if ($vendorSekolah instanceof VendorSekolah) {
                                            if ($vendorSekolah->getJenis() == 'khusus') {
                                                $messenger->setUseVendor(true);
                                                $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                            }
                                        }
                                        $messenger->setPhoneNumber($ponsel);
                                        $messenger->setMessage($tekstemplate);
                                        $messenger->sendMessage($sekolah);
                                        $terkirim = true;
                                    }
                                }

                                if ($terkirim) {
                                    $kehadiran->setSmsTerproses($terkirim);
                                    $em->persist($kehadiran);
                                }
                            }
                        }
                    }
                }
            }

            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $jadwalKehadiran->getTahunAkademik(),
                    'kelas' => $jadwalKehadiran->getKelas(),
                    'tanggal' => $tanggalTerpilih,
                    'berhasilKirimSms' => false,
                ])
            ;

            if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                $prosesKehadiranSiswa->setBerhasilKirimSms(true);
                $em->persist($prosesKehadiranSiswa);
            }

            $em->flush();

            $return['responseCode'] = 200;
            $return['responseText'] = $translator->trans('flash.sms.kehadiran.terkirim');
            $return['berhasilKirimSms'] = 1;
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        } else {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.form.tidak.valid");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * @Route("/kirim-sms-ringkasan/{wali_kelas_id}/{tanggal}", name="kehadiran-siswa_kirimsmsringkasan")
     * @Method("POST")
     * @Secure(roles="ROLE_GURU_PIKET")
     */
    public function kirimSmsRingkasanAction($wali_kelas_id, $tanggal)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $translator = $this->get('translator');
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $tanggalTerpilih = new \DateTime($tanggal);

        $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;
        if (!$vendorSekolah instanceof VendorSekolah) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.vendor.sekolah.tidak.tersedia");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $layananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findOneBy([
                'sekolah' => $sekolah,
                'jenisLayanan' => 'zza-ringkasan-kehadiran',
                'status' => true,
            ])
        ;
        if (!$layananSms instanceof PilihanLayananSms) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.layanan.sms.ringkasan.kehadiran.tidak.aktif");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah,
            ])
        ;
        if (!$tahunAkademik instanceof TahunAkademik) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.tahun.akademik.tidak.ada.yang.aktif");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
            ->findOneBy([
                'sekolah' => $sekolah,
                'tanggal' => $tanggalTerpilih,
                'kbm' => true,
            ])
        ;
        if (!$kalenderPendidikan instanceof KalenderPendidikan) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.bukan.hari.kbm.aktif");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $waliKelas = $em->getRepository('LanggasSisdikBundle:WaliKelas')->find($wali_kelas_id);
        if (!$waliKelas instanceof WaliKelas) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.wali.kelas.tidak.ada");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
        if (!$waliKelas->getTemplatesmsIkhtisarKehadiran() instanceof Templatesms) {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.template.sms.ringkasan.wali.kelas.tidak.tersedia");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
        if ($waliKelas->getUser()->getNomorPonsel() == '') {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.ponsel.wali.kelas.kosong");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }

        $formKirimSmsRingkasan = $this->createForm('sisdik_kehadiransiswasms_ringkasan', null, [
            'wali_kelas' => $waliKelas,
            'tanggal' => $tanggal,
        ]);
        $formKirimSmsRingkasan->submit($this->getRequest());

        if ($formKirimSmsRingkasan->isValid()) {
            $kehadiranSiswa = $em->createQueryBuilder()
                ->select('kehadiran')
                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                ->where('kehadiran.sekolah = :sekolah')
                ->andWhere('kehadiran.tahunAkademik = :tahunakademik')
                ->andWhere('kehadiran.kelas = :kelas')
                ->andWhere('kehadiran.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunakademik', $tahunAkademik)
                ->setParameter('kelas', $waliKelas->getKelas())
                ->setParameter('tanggal', $tanggalTerpilih->format("Y-m-d"))
                ->getQuery()
                ->useQueryCache(true)
                ->getResult()
            ;

            if (count($kehadiranSiswa) <= 0) {
                continue;
            }

            $jumlahTepat = 0;
            $jumlahTelat = 0;
            $jumlahAlpa = 0;
            $jumlahIzin = 0;
            $jumlahSakit = 0;

            /* @var $kehadiran KehadiranSiswa */
            foreach ($kehadiranSiswa as $kehadiran) {
                switch ($kehadiran->getStatusKehadiran()) {
                    case 'a-hadir-tepat':
                        $jumlahTepat++;
                        break;
                    case 'b-hadir-telat':
                        $jumlahTelat++;
                        break;
                    case 'c-alpa':
                        $jumlahAlpa++;
                        break;
                    case 'd-izin':
                        $jumlahIzin++;
                        break;
                    case 'e-sakit':
                        $jumlahSakit++;
                        break;
                }
            }

            $teksRingkasan = $waliKelas->getTemplatesmsIkhtisarKehadiran()->getTeks();

            $teksRingkasan = str_replace("%nama%", $waliKelas->getUser()->getName(), $teksRingkasan);
            $teksRingkasan = str_replace("%kelas%", $waliKelas->getKelas()->getNama(), $teksRingkasan);

            $indeksHari = $tanggalTerpilih->format('N');
            $teksRingkasan = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$indeksHari]), $teksRingkasan);

            $teksRingkasan = str_replace("%tanggal%", $tanggalTerpilih->format('d/m/Y'), $teksRingkasan);
            $teksRingkasan = str_replace("%jumlah-tepat%", $jumlahTepat, $teksRingkasan);
            $teksRingkasan = str_replace("%jumlah-telat%", $jumlahTelat, $teksRingkasan);
            $teksRingkasan = str_replace("%jumlah-alpa%", $jumlahAlpa, $teksRingkasan);
            $teksRingkasan = str_replace("%jumlah-sakit%", $jumlahIzin, $teksRingkasan);
            $teksRingkasan = str_replace("%jumlah-izin%", $jumlahSakit, $teksRingkasan);

            $terkirim = false;
            $nomorponsel = preg_split("/[\s,\/]+/", $waliKelas->getUser()->getNomorPonsel());
            foreach ($nomorponsel as $ponsel) {
                $messenger = $this->container->get('sisdik.messenger');
                if ($messenger instanceof Messenger) {
                    if ($vendorSekolah->getJenis() == 'khusus') {
                        $messenger->setUseVendor(true);
                        $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                    }
                    $messenger->setPhoneNumber($ponsel);
                    $messenger->setMessage($teksRingkasan);
                    $messenger->sendMessage($sekolah);

                    $terkirim = true;
                }
            }

            if ($terkirim) {
                $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'tahunAkademik' => $tahunAkademik,
                        'kelas' => $waliKelas->getKelas(),
                        'tanggal' => $tanggalTerpilih,
                        'berhasilKirimSmsRingkasan' => false,
                    ])
                ;

                if ($prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                    $prosesKehadiranSiswa->setBerhasilKirimSmsRingkasan(true);
                    $em->persist($prosesKehadiranSiswa);
                }
            }

            $em->flush();

            $return['responseCode'] = 200;
            $return['responseText'] = $translator->trans('flash.sms.ringkasan.kehadiran.terkirim');
            $return['berhasilKirimSmsRingkasan'] = 1;
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        } else {
            $return['responseCode'] = 400;
            $return['responseText'] = $translator->trans("error.form.tidak.valid");
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Memperbarui kehadiran siswa berdasarkan data yang diambil secara manual.
     *
     * @Route("/pembaruan-manual/{urutan}/{daftarJadwal}", name="kehadiran-siswa_manual")
     * @Secure(roles="ROLE_GURU_PIKET")
     */
    public function pembaruanManualAction($urutan = 0, $daftarJadwal = "0")
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $retval = [];
        $response = new Response('', 200, [
            'Content-Type' => 'application/json',
        ]);

        $daftarStatusKehadiran = JadwalKehadiran::getDaftarStatusKehadiran();
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $tanggalSekarang = $waktuSekarang->format('Y-m-d');
        $jam = $waktuSekarang->format('H:i').':00';
        $mingguanHariKe = $waktuSekarang->format('N');
        $bulananHariKe = $waktuSekarang->format('j');

        $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
            ->findOneBy([
                'sekolah' => $sekolah,
                'tanggal' => $waktuSekarang,
                'kbm' => true,
            ])
        ;

        if (!(is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan)) {
            $retval['selesai'] = 1;
            $retval['pesan'][] = "Hari sekarang bukan hari yang ditandai sebagai KBM aktif";
            $response->setContent(json_encode($retval));

            return $response;
        }

        $qbJadwalTotal = $em->createQueryBuilder()
            ->select('COUNT(jadwal.id)')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwal')
            ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
            ->andWhere('jadwal.sekolah = :sekolah')
            ->andWhere('jadwal.paramstatusHinggaJam <= :jam')
            ->andWhere('tahunAkademik.aktif = :aktif')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('jam', $jam)
            ->setParameter('aktif', true)
        ;
        $jadwalTotal = $qbJadwalTotal->getQuery()->getSingleScalarResult();
        if ($urutan >= $jadwalTotal) {
            $retval['selesai'] = 1;
            $retval['pesan'][] = "Selesai memperbarui kehadiran siswa";
            $response->setContent(json_encode($retval));

            return $response;
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwal')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwal')
            ->leftJoin('jadwal.tahunAkademik', 'tahunAkademik')
            ->andWhere('jadwal.sekolah = ?1')
            ->andWhere('jadwal.paramstatusHinggaJam <= ?2')
            ->andWhere('tahunAkademik.aktif = ?3')
            ->andWhere('jadwal.id NOT IN (?4)')
            ->andWhere("jadwal.perulangan = ?5 OR (jadwal.perulangan = ?6 AND jadwal.mingguanHariKe = ?8) OR (jadwal.perulangan = ?7 AND jadwal.bulananHariKe = ?9)")
            ->andWhere("jadwal.paramstatusDariJam != '' OR jadwal.paramstatusHinggaJam != ''")
            ->setParameter(1, $sekolah)
            ->setParameter(2, $jam)
            ->setParameter(3, true)
            ->setParameter(4, preg_split('/,/', $daftarJadwal))
            ->orderBy('jadwal.paramstatusHinggaJam', 'ASC')
        ;

        $tempCounter = 5;
        foreach ($perulangan as $key => $value) {
            $querybuilder
                ->setParameter($tempCounter, $key)
            ;
            $tempCounter++;

            if ($key == 'b-mingguan') {
                $querybuilder
                    ->setParameter(8, $mingguanHariKe)
                ;
            } elseif ($key == 'c-bulanan') {
                $querybuilder
                    ->setParameter(9, $bulananHariKe)
                ;
            }
        }

        $jadwalKehadiran = $querybuilder->getQuery()->getResult();

        foreach ($jadwalKehadiran as $jadwal) {
            if (!(is_object($jadwal) && $jadwal instanceof JadwalKehadiran)) {
                continue;
            }
            if ($jadwal->getParamstatusHinggaJam() == '') {
                continue;
            }

            $dariJam = $jadwal->getParamstatusDariJam();
            $hinggaJam = $jadwal->getParamstatusHinggaJam();
            $tanggalJadwalDari = new \DateTime(date("Y-m-d $dariJam"));
            $tanggalJadwalHingga = new \DateTime(date("Y-m-d $hinggaJam"));

            $logDirectory = $this->container->get('kernel')->getRootDir()
                .DIRECTORY_SEPARATOR
                ."fingerprintlogs"
                .DIRECTORY_SEPARATOR
                .$sekolah->getId()
                .DIRECTORY_SEPARATOR
                .'log'
                .DIRECTORY_SEPARATOR
                .'manual'
                .DIRECTORY_SEPARATOR
                .$tanggalSekarang
            ;
            if (!is_dir($logDirectory)) {
                continue;
            }

            $retval['pesan'][] = "Memproses kehadiran siswa untuk jadwal "
                .$jadwal->getTahunAkademik()->getNama()
                .", "
                .$jadwal->getKelas()->getNama()
                .", "
                .$perulangan[$jadwal->getPerulangan()]
                .", "
                ./** @Ignore */ $this->get('translator')->trans($daftarStatusKehadiran[$jadwal->getStatusKehadiran()])
                .", "
                .$jadwal->getParamstatusDariJam(false)
                ." - "
                .$jadwal->getParamstatusHinggaJam(false)
            ;
            $retval['daftarJadwal'] = $daftarJadwal == '' ? $jadwal->getId() : $daftarJadwal.','.$jadwal->getId();
            $retval['urutan'] = ++$urutan;

            $mesinFingerprint = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')
                ->findBy([
                    'sekolah' => $sekolah,
                    'aktif' => true,
                ])
            ;

            $counterJumlahTerproses = 0;

            foreach ($mesinFingerprint as $mesin) {
                if (!(is_object($mesin) && $mesin instanceof MesinKehadiran)) {
                    continue;
                }
                if ($mesin->getAlamatIp() == '') {
                    continue;
                }

                $logFile = exec("cd $logDirectory && ls -1 {$mesin->getAlamatIp()}* | tail -1");
                $sourceFile = $logDirectory.DIRECTORY_SEPARATOR.$logFile;
                $targetFile = self::TMP_DIR
                    .DIRECTORY_SEPARATOR
                    .$sekolah->getId()
                    .'-sisdik-'
                    .uniqid(mt_rand(), true)
                    .$logFile
                ;

                if (!@copy($sourceFile, $targetFile)) {
                    continue;
                }

                $output = [];
                exec("gunzip --force $targetFile", $output);
                $extractedFile = substr($targetFile, 0, -3);

                if (strstr($targetFile, 'json') !== false) {
                    $buffer = file_get_contents($extractedFile);

                    $logKehadiran = json_decode($buffer, true);

                    foreach ($logKehadiran as $item) {
                        $logTanggal = new \DateTime($item['datetime']);

                        // +60 detik perbedaan
                        if (!($logTanggal->getTimestamp() >= $tanggalJadwalDari->getTimestamp() && $logTanggal->getTimestamp() <= $tanggalJadwalHingga->getTimestamp() + 60)) {
                            continue;
                        }

                        if ($logTanggal->format('Ymd') != $waktuSekarang->format('Ymd')) {
                            continue;
                        }

                        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                            ->findOneBy([
                                'nomorIndukSistem' => $item['id'],
                            ])
                        ;

                        if (is_object($siswa) && $siswa instanceof Siswa) {
                            $kehadiranSiswa = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                                ->findOneBy([
                                    'sekolah' => $sekolah,
                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                    'kelas' => $jadwal->getKelas(),
                                    'siswa' => $siswa,
                                    'tanggal' => $waktuSekarang,
                                    'permulaan' => true,
                                ])
                            ;
                            if (is_object($kehadiranSiswa) && $kehadiranSiswa instanceof KehadiranSiswa) {
                                /**$retval['pesan'][] = "··· Memperbarui kehadiran siswa "
                                    . $kehadiranSiswa->getSiswa()->getNamaLengkap()
                                    . " ("
                                    . $kehadiranSiswa->getSiswa()->getNomorIndukSistem()
                                    . ")"
                                ;**/
                                $counterJumlahTerproses++;

                                $kehadiranSiswa->setPermulaan(false);
                                $kehadiranSiswa->setStatusKehadiran($jadwal->getStatusKehadiran());
                                $kehadiranSiswa->setJam($logTanggal->format('H:i:s'));

                                $em->persist($kehadiranSiswa);
                                $em->flush();
                            }
                        }
                    }

                    $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                        ->findOneBy([
                            'sekolah' => $sekolah,
                            'tahunAkademik' => $jadwal->getTahunAkademik(),
                            'kelas' => $jadwal->getKelas(),
                            'tanggal' => $waktuSekarang,
                            'berhasilDiperbaruiMesin' => false,
                        ])
                    ;

                    if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                        $prosesKehadiranSiswa->setBerhasilDiperbaruiMesin(true);
                        $em->persist($prosesKehadiranSiswa);
                    }
                } else {
                    exec("sed -i -n '/<.*>/,\$p' $extractedFile");

                    $buffer = file_get_contents($extractedFile);
                    $buffer = preg_replace("/\s+/", ' ', trim($buffer));
                    $xmlstring = "<?xml version='1.0'?>\n".$buffer;

                    $xmlobject = @simplexml_load_string($xmlstring);

                    if ($xmlobject) {
                        foreach ($xmlobject->xpath('Row') as $item) {
                            $logTanggal = new \DateTime($item->DateTime);

                            // +60 detik perbedaan
                            if (!($logTanggal->getTimestamp() >= $tanggalJadwalDari->getTimestamp() && $logTanggal->getTimestamp() <= $tanggalJadwalHingga->getTimestamp() + 60)) {
                                continue;
                            }

                            if ($logTanggal->format('Ymd') != $waktuSekarang->format('Ymd')) {
                                continue;
                            }

                            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                                ->findOneBy([
                                    'nomorIndukSistem' => $item->PIN,
                                ])
                            ;

                            if (is_object($siswa) && $siswa instanceof Siswa) {
                                $kehadiranSiswa = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                                    ->findOneBy([
                                        'sekolah' => $sekolah,
                                        'tahunAkademik' => $jadwal->getTahunAkademik(),
                                        'kelas' => $jadwal->getKelas(),
                                        'siswa' => $siswa,
                                        'tanggal' => $waktuSekarang,
                                        'permulaan' => true,
                                    ])
                                ;
                                if (is_object($kehadiranSiswa) && $kehadiranSiswa instanceof KehadiranSiswa) {
                                    /**$retval['pesan'][] = "··· Memperbarui kehadiran siswa "
                                        . $kehadiranSiswa->getSiswa()->getNamaLengkap()
                                        . " ("
                                        . $kehadiranSiswa->getSiswa()->getNomorIndukSistem()
                                        . ")"
                                    ;**/
                                    $counterJumlahTerproses++;

                                    $kehadiranSiswa->setPermulaan(false);
                                    $kehadiranSiswa->setStatusKehadiran($jadwal->getStatusKehadiran());
                                    $kehadiranSiswa->setJam($logTanggal->format('H:i:s'));

                                    $em->persist($kehadiranSiswa);
                                    $em->flush();
                                }
                            }
                        }

                        $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                'kelas' => $jadwal->getKelas(),
                                'tanggal' => $waktuSekarang,
                                'berhasilDiperbaruiMesin' => false,
                            ])
                        ;

                        if (is_object($prosesKehadiranSiswa) && $prosesKehadiranSiswa instanceof ProsesKehadiranSiswa) {
                            $prosesKehadiranSiswa->setBerhasilDiperbaruiMesin(true);
                            $em->persist($prosesKehadiranSiswa);
                        }
                    }
                }

                @unlink($extractedFile);
            }

            $em->flush();

            $retval['pesan'][] = "»»» Jumlah kehadiran siswa terbarui: $counterJumlahTerproses";
            $response->setContent(json_encode($retval));

            return $response;
        }

        $response->setContent(json_encode($retval));

        return $response;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.presence', [], 'navigations')][$translator->trans('links.kehadiran.siswa', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
