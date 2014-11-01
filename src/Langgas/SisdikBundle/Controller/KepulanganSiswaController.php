<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\ProsesKepulanganSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Langgas\SisdikBundle\Entity\Tingkat;
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\TranslationBundle\Annotation\Ignore;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;

/**
 * @Route("/kepulangan-siswa")
 * @PreAuthorize("hasRole('ROLE_GURU_PIKET') or hasRole('ROLE_GURU')")
 */
class KepulanganSiswaController extends Controller
{
    const TMP_DIR = "/tmp";

    /**
     * @Route("/", name="kepulangan-siswa")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kepulangansiswasearch');

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
            'tahunAkademik' => $tahunAkademik,
            'mesinWakil' => $mesinWakil,
        ];
    }

    /**
     * @Route("/edit", name="kepulangan-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kepulangansiswasearch');

        $querybuilder = $em->createQueryBuilder()
            ->select('kepulangan')
            ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
            ->leftJoin('kepulangan.kelas', 'kelas')
            ->leftJoin('kepulangan.siswa', 'siswa')
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
                    'tanggal' => $searchdata['tanggal']
                ])
            ;

            if (!(is_object($kbmAktif) && $kbmAktif instanceof KalenderPendidikan)) {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.kepulangan.siswa.bukan.hari.kbm.aktif'))
                ;

                return $this->redirect($this->generateUrl('kepulangan-siswa'));
            }

            if ($searchdata['tanggal'] instanceof \DateTime) {
                $querybuilder->andWhere('kepulangan.tanggal = :tanggal');
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

            if ($searchdata['statusKepulangan'] != '') {
                $querybuilder->andWhere("kepulangan.statusKepulangan = :statusKepulangan");
                $querybuilder->setParameter('statusKepulangan', $searchdata['statusKepulangan']);

                $buildparam['statusKepulangan'] = $searchdata['statusKepulangan'];
            } else {
                $buildparam['statusKepulangan'] = '';
            }

            $entities = $querybuilder->getQuery()->getResult();

            $students = $this->createForm('sisdik_kepulangansiswa', null, ['buildparam' => $buildparam]);

            $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                ->findOneBy([
                    'aktif' => true,
                    'sekolah' => $sekolah,
                ])
            ;

            $prosesKepulanganSiswa = null;
            $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'tanggal' => $searchdata['tanggal'],
                ])
            ;

            $formInisiasi = $this->createForm('sisdik_kepulangansiswainisiasi', null, [
                'kelas' => $kelas,
                'tanggal' => $searchdata['tanggal']->format('Y-m-d'),
            ]);

            $formSms = $this->createForm('sisdik_kepulangansiswasms', null, [
                'kelas' => $kelas,
                'tanggal' => $searchdata['tanggal']->format('Y-m-d'),
                'kepulangan' => $entities,
            ]);

            return [
                'kelas' => $kelas,
                'entities' => $entities,
                'form' => $students->createView(),
                'searchform' => $searchform->createView(),
                'buildparam' => $buildparam,
                'tahunAkademik' => $tahunAkademik,
                'prosesKepulanganSiswa' => $prosesKepulanganSiswa,
                'tanggal' => $searchdata['tanggal'],
                'formInisiasi' => $formInisiasi->createView(),
                'formSms' => $formSms->createView(),
            ];
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.kepulangan.siswa.pencarian.gagal'))
            ;

            return $this->redirect($this->generateUrl('kepulangan-siswa'));
        }
    }

    /**
     * Memperbarui kepulangan siswa
     *
     * @Route("/update", name="kepulangan-siswa_update")
     * @Method("POST")
     */
    public function updateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->get('sisdik_kepulangansiswa');

        foreach ($data as $keys => $values) {
            if (preg_match('/kepulangan_(\d+)$/', $keys, $matches) !== FALSE) {
                if (array_key_exists(1, $matches)) {
                    $kepulangan = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')->find($matches[1]);
                    if (is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa) {
                        $kepulangan->setStatusKepulangan($values);
                        $kepulangan->setPermulaan(false);
                        $kepulangan->setTervalidasi(true);
                        $kepulangan->setKeteranganStatus($data['kepulangan_keterangan_' . $matches[1]]);
                        $em->persist($kepulangan);
                    }
                }
            }
        }

        $return = [];
        if (is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa) {
            $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                ->findOneBy([
                    'sekolah' => $kepulangan->getSekolah(),
                    'tahunAkademik' => $kepulangan->getTahunAkademik(),
                    'kelas' => $kepulangan->getKelas(),
                    'tanggal' => $kepulangan->getTanggal(),
                ])
            ;

            if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                $prosesKepulanganSiswa->setBerhasilValidasi(true);
            } else {
                $prosesKepulanganSiswa = new ProsesKepulanganSiswa();
                $prosesKepulanganSiswa->setSekolah($kepulangan->getSekolah());
                $prosesKepulanganSiswa->setTahunAkademik($kepulangan->getTahunAkademik());
                $prosesKepulanganSiswa->setKelas($kepulangan->getKelas());
                $prosesKepulanganSiswa->setTanggal($kepulangan->getTanggal());
                $prosesKepulanganSiswa->setBerhasilValidasi(true);
            }

            $em->persist($prosesKepulanganSiswa);
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
     * Menginisiasi kepulangan siswa
     *
     * @Route("/inisiasi/{kelas_id}/{tanggal}", name="kepulangan-siswa_inisiasi")
     * @Method("POST")
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

        $formInisiasi = $this->createForm('sisdik_kepulangansiswainisiasi', null, [
                'kelas' => $kelas,
                'tanggal' => $tanggal,
            ])
            ->submit($this->getRequest())
        ;

        if ($formInisiasi->isValid()) {
            $statusKepulangan = $formInisiasi->get('statusKepulangan')->getData();

            $qbKepulangan = $em->createQueryBuilder()
                ->select('kepulangan')
                ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                ->where('kepulangan.sekolah = :sekolah')
                ->andWhere('kepulangan.tahunAkademik = :tahunAkademik')
                ->andWhere('kepulangan.kelas = :kelas')
                ->andWhere('kepulangan.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunAkademik', $tahunAkademik)
                ->setParameter('kelas', $kelas)
                ->setParameter('tanggal', $tanggal)
            ;
            $entities = $qbKepulangan->getQuery()->getResult();

            if (count($entities) > 0) {
                foreach ($entities as $kepulangan) {
                    if (is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa) {
                        $kepulangan->setKeteranganStatus(null);
                        $kepulangan->setPermulaan(true);
                        $kepulangan->setTervalidasi(false);
                        $kepulangan->setSmsDlr(null);
                        $kepulangan->setSmsDlrtime(null);
                        $kepulangan->setSmsTerproses(false);
                        $kepulangan->setStatusKepulangan($statusKepulangan);

                        $em->persist($kepulangan);
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

                $jam = new \DateTime();
                foreach ($entitiesSiswaKelas as $siswaKelas) {
                    if (!(is_object($siswaKelas) && $siswaKelas instanceof SiswaKelas)) {
                        continue;
                    }

                    $qbKepulangan = $em->createQueryBuilder()
                        ->select('kepulangan')
                        ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
                        ->where('kepulangan.sekolah = :sekolah')
                        ->andWhere('kepulangan.siswa = :siswa')
                        ->andWhere('kepulangan.tanggal = :tanggal')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('siswa', $siswaKelas->getSiswa())
                        ->setParameter('tanggal', $tanggal)
                    ;
                    $entityKepulangan = $qbKepulangan->getQuery()->getResult();

                    if (count($entityKepulangan) >= 1) {
                        continue;
                    }

                    $kepulangan = new KepulanganSiswa();
                    $kepulangan->setSekolah($sekolah);
                    $kepulangan->setTahunAkademik($tahunAkademik);
                    $kepulangan->setKelas($kelas);
                    $kepulangan->setSiswa($siswaKelas->getSiswa());
                    $kepulangan->setStatusKepulangan($statusKepulangan);
                    $kepulangan->setPermulaan(true);
                    $kepulangan->setTervalidasi(false);
                    $kepulangan->setTanggal(new \DateTime($tanggal));
                    $kepulangan->setJam($jam->format('H:i') . ':00');
                    $kepulangan->setSmsTerproses(false);

                    $kehadiran = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')
                        ->findOneBy([
                            'sekolah' => $sekolah,
                            'tahunAkademik' => $tahunAkademik,
                            'kelas' => $kelas,
                            'siswa' => $siswaKelas->getSiswa(),
                            'tanggal' => new \DateTime($tanggal),
                        ])
                    ;

                    if ($kehadiran instanceof KehadiranSiswa) {
                        $kepulangan->setKehadiranSiswa($kehadiran);
                        $em->persist($kepulangan);
                    }
                }
            }

            $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'tanggal' => new \DateTime($tanggal),
                ])
            ;
            if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                $prosesKepulanganSiswa->setBerhasilInisiasi(true);
            } else {
                $prosesKepulanganSiswa = new ProsesKepulanganSiswa();
                $prosesKepulanganSiswa->setSekolah($sekolah);
                $prosesKepulanganSiswa->setTahunAkademik($tahunAkademik);
                $prosesKepulanganSiswa->setKelas($kelas);
                $prosesKepulanganSiswa->setTanggal(new \DateTime($tanggal));
                $prosesKepulanganSiswa->setBerhasilInisiasi(true);
            }

            $em->persist($prosesKepulanganSiswa);

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
     * Mengirim SMS kepulangan
     *
     * @Route("/kirim-sms/{kelas_id}/{tanggal}", name="kepulangan-siswa_kirimsms")
     * @Method("POST")
     */
    public function kirimSmsAction($kelas_id, $tanggal)
    {
        $sekolah = $this->getSekolah();
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $translator = $this->get('translator');
        $namaNamaHari = JadwalKehadiran::getNamaHari();
        $tanggalTerpilih = new \DateTime($tanggal);
        $mingguanHariKe = $tanggalTerpilih->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $tanggalTerpilih->format('j');

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        $kelas = $em->getRepository('LanggasSisdikBundle:Kelas')->find($kelas_id);

        $qbKepulanganSiswa = $em->createQueryBuilder()
            ->select('kepulangan')
            ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
            ->leftJoin('kepulangan.kelas', 'kelas')
            ->leftJoin('kepulangan.siswa', 'siswa')
            ->where('kepulangan.sekolah = :sekolah')
            ->andWhere('kepulangan.kelas = :kelas')
            ->andWhere('kepulangan.tanggal = :tanggal')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('kelas', $kelas)
            ->setParameter('tanggal', $tanggalTerpilih->format('Y-m-d'))
        ;
        $kepulanganSiswa = $qbKepulanganSiswa->getQuery()->getResult();

        $formKirimSms = $this->createForm('sisdik_kepulangansiswasms', null, [
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'kepulangan' => $kepulanganSiswa,
        ]);
        $formKirimSms->submit($this->getRequest());

        if ($formKirimSms->isValid()) {
            $statusKepulangan = $formKirimSms->get('statusKepulangan')->getData();
            $siswa = $formKirimSms->get('siswa')->getData();

            $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                ->findOneBy([
                    'sekolah' => $sekolah,
                ])
            ;

            // PERINGATAN: diasumsikan bahwa perulangan apapun
            // menggunakan template sms yang serupa :(
            // TODO: Mengirim sms manual sebaiknya bisa memilih template sms
            $jadwalKepulangan = $em->getRepository('LanggasSisdikBundle:JadwalKepulangan')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                    'kirimSms' => true,
                    'statusKepulangan' => $statusKepulangan,
                ])
            ;
            if (!($jadwalKepulangan instanceof JadwalKepulangan)) {
                $return['responseCode'] = 400;
                $return['responseText'] = "tidak ada jadwal yang sesuai atau jadwal tidak diatur untuk bisa mengirim sms";
                $return = json_encode($return);

                return new Response($return, 200, ['Content-Type' => 'application/json']);
            }

            switch ($jadwalKepulangan->getStatusKepulangan()) {
                case 'a-pulang-tercatat':
                    $jenisLayananSms = 'u-kepulangan-tercatat';
                    break;
                case 'b-pulang-tak-tercatat':
                    $jenisLayananSms = 'v-kepulangan-tak-tercatat';
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
                $kepulangan = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'tahunAkademik' => $tahunAkademik,
                        'kelas' => $kelas,
                        'siswa' => $siswa,
                        'statusKepulangan' => $statusKepulangan,
                        'tanggal' => $tanggalTerpilih,
                    ])
                ;
                if (!(is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa)) {
                    $return['responseCode'] = 400;
                    $return['responseText'] = "kepulangan siswa yang terpilih tak ditemukan";
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
                        $tekstemplate = $jadwalKepulangan->getTemplatesms()->getTeks();
                        $tekstemplate = str_replace("%nama%", $kepulangan->getSiswa()->getNamaLengkap(), $tekstemplate);
                        $tekstemplate = str_replace("%nis%", $kepulangan->getSiswa()->getNomorInduk(), $tekstemplate);
                        $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$mingguanHariKe]), $tekstemplate);
                        $tekstemplate = str_replace("%tanggal%", $tanggalTerpilih->format('d/m/Y'), $tekstemplate);
                        $tekstemplate = str_replace("%jam%", $kepulangan->getJam(), $tekstemplate);
                        $tekstemplate = str_replace("%keterangan%", $kepulangan->getKeteranganStatus(), $tekstemplate);

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
                            $kepulangan->setSmsTerproses($terkirim);
                            $em->persist($kepulangan);
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
                $qbKepulanganSiswa
                    ->andWhere('kepulangan.statusKepulangan = :status')
                    ->setParameter('status', $statusKepulangan)
                ;
                $kepulanganSiswaPerStatus = $qbKepulanganSiswa->getQuery()->getResult();

                foreach ($kepulanganSiswaPerStatus as $kepulangan) {
                    if (is_object($kepulangan) && $kepulangan instanceof KepulanganSiswa) {
                        $ortuWaliAktif = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')
                            ->findOneBy([
                                'siswa' => $kepulangan->getSiswa(),
                                'aktif' => true,
                            ])
                        ;
                        if ((is_object($ortuWaliAktif) && $ortuWaliAktif instanceof OrangtuaWali)) {
                            $ponselOrtuWaliAktif = $ortuWaliAktif->getPonsel();
                            if ($ponselOrtuWaliAktif != "") {
                                $tekstemplate = $jadwalKepulangan->getTemplatesms()->getTeks();
                                $tekstemplate = str_replace("%nama%", $kepulangan->getSiswa()->getNamaLengkap(), $tekstemplate);
                                $tekstemplate = str_replace("%nis%", $kepulangan->getSiswa()->getNomorInduk(), $tekstemplate);
                                $tekstemplate = str_replace("%hari%", /** @Ignore */ $translator->trans($namaNamaHari[$mingguanHariKe]), $tekstemplate);
                                $tekstemplate = str_replace("%tanggal%", $tanggalTerpilih->format('d/m/Y'), $tekstemplate);
                                $tekstemplate = str_replace("%jam%", $kepulangan->getJam(), $tekstemplate);
                                $tekstemplate = str_replace("%keterangan%", $kepulangan->getKeteranganStatus(), $tekstemplate);

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
                                    $kepulangan->setSmsTerproses($terkirim);
                                    $em->persist($kepulangan);
                                }
                            }
                        }
                    }
                }
            }

            $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah,
                    'tahunAkademik' => $jadwalKepulangan->getTahunAkademik(),
                    'kelas' => $jadwalKepulangan->getKelas(),
                    'tanggal' => $tanggalTerpilih,
                    'berhasilKirimSms' => false,
                ])
            ;

            if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                $prosesKepulanganSiswa->setBerhasilKirimSms(true);
                $em->persist($prosesKepulanganSiswa);
            }

            $em->flush();

            $return['responseCode'] = 200;
            $return['responseText'] = $translator->trans('flash.sms.kepulangan.terkirim');
            $return['berhasilKirimSms'] = 1;
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        } else {
            $return['responseCode'] = 400;
            $return['responseText'] = "form tidak valid";
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Memperbarui kepulangan siswa berdasarkan data yang diambil secara manual
     *
     * @Route("/pembaruan-manual/{urutan}/{daftarJadwal}", name="kepulangan-siswa_manual")
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

        $daftarStatusKepulangan = JadwalKepulangan::getDaftarStatusKepulangan();
        $perulangan = JadwalKehadiran::getDaftarPerulangan();
        $waktuSekarang = new \DateTime();
        $tanggalSekarang = $waktuSekarang->format('Y-m-d');
        $jam = $waktuSekarang->format('H:i') . ':00';
        $mingguanHariKe = $waktuSekarang->format('w');
        $mingguanHariKe = $mingguanHariKe - 1 == -1 ? 7 : $mingguanHariKe - 1;
        $bulananHariKe = $waktuSekarang->format('j');

        $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
            ->findOneBy([
                'sekolah' => $sekolah,
                'tanggal' => $waktuSekarang,
                'kbm' => true,
            ])
        ;

        if (!(is_object($kalenderPendidikan) && $kalenderPendidikan instanceof KalenderPendidikan)) {
            $retval['pesan'][] = "Hari sekarang bukan hari yang ditandai sebagai KBM aktif";
            $response->setContent(json_encode($retval));

            return $response;
        }

        $qbJadwalTotal = $em->createQueryBuilder()
            ->select('COUNT(jadwal.id)')
            ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwal')
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
            $retval['pesan'][] = "Selesai memperbarui jadwal kepulangan";
            $response->setContent(json_encode($retval));

            return $response;
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwal')
            ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwal')
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

        $jadwalKepulangan = $querybuilder->getQuery()->getResult();

        foreach ($jadwalKepulangan as $jadwal) {
            if (!(is_object($jadwal) && $jadwal instanceof JadwalKepulangan)) {
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
                . DIRECTORY_SEPARATOR
                . "fingerprintlogs"
                . DIRECTORY_SEPARATOR
                . $sekolah->getId()
                . DIRECTORY_SEPARATOR
                . 'log'
                . DIRECTORY_SEPARATOR
                . 'manual'
                . DIRECTORY_SEPARATOR
                . $tanggalSekarang
            ;
            if (!is_dir($logDirectory)) {
                continue;
            }

            $retval['pesan'][] = "Memproses kepulangan siswa untuk jadwal "
                . $jadwal->getTahunAkademik()->getNama()
                . ", "
                . $jadwal->getKelas()->getNama()
                . ", "
                . $perulangan[$jadwal->getPerulangan()]
                . ", "
                . /** @Ignore */ $this->get('translator')->trans($daftarStatusKepulangan[$jadwal->getStatusKepulangan()])
                . ", "
                . $jadwal->getParamstatusDariJam(false)
                . " - "
                . $jadwal->getParamstatusHinggaJam(false)
            ;
            $retval['daftarJadwal'] = $daftarJadwal == '' ? $jadwal->getId() : $daftarJadwal . ',' . $jadwal->getId();
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
                $sourceFile = $logDirectory . DIRECTORY_SEPARATOR . $logFile;
                $targetFile = self::TMP_DIR
                    . DIRECTORY_SEPARATOR
                    . $sekolah->getId()
                    . '-sisdik-'
                    . uniqid(mt_rand(), true)
                    . $logFile
                ;

                if (!@copy($sourceFile, $targetFile)) {
                    continue;
                }

                $output = [];
                exec("gunzip --force $targetFile", $output);

                $buffer = file_get_contents(substr($targetFile, 0, -3));

                if (strstr($targetFile, 'json') !== false) {
                    $logKepulangan = json_decode($buffer, true);

                    foreach ($logKepulangan as $item) {
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
                            $kepulanganSiswa = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                                ->findOneBy([
                                    'sekolah' => $sekolah,
                                    'tahunAkademik' => $jadwal->getTahunAkademik(),
                                    'kelas' => $jadwal->getKelas(),
                                    'siswa' => $siswa,
                                    'tanggal' => $waktuSekarang,
                                    'permulaan' => true,
                                ])
                            ;
                            if (is_object($kepulanganSiswa) && $kepulanganSiswa instanceof KepulanganSiswa) {
                                /**$retval['pesan'][] = "··· Memperbarui kepulangan siswa "
                                    . $kepulanganSiswa->getSiswa()->getNamaLengkap()
                                    . " ("
                                    . $kepulanganSiswa->getSiswa()->getNomorIndukSistem()
                                    . ")"
                                ;**/
                                $counterJumlahTerproses++;

                                $kepulanganSiswa->setPermulaan(false);
                                $kepulanganSiswa->setStatusKepulangan($jadwal->getStatusKepulangan());
                                $kepulanganSiswa->setJam($logTanggal->format('H:i:s'));

                                $em->persist($kepulanganSiswa);
                                $em->flush();
                            }
                        }
                    }

                    $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                        ->findOneBy([
                            'sekolah' => $sekolah,
                            'tahunAkademik' => $jadwal->getTahunAkademik(),
                            'kelas' => $jadwal->getKelas(),
                            'tanggal' => $waktuSekarang,
                            'berhasilDiperbaruiMesin' => false,
                        ])
                    ;

                    if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                        $prosesKepulanganSiswa->setBerhasilDiperbaruiMesin(true);
                        $em->persist($prosesKepulanganSiswa);
                    }
                } else {
                    $buffer = preg_replace("/\s+/", ' ', trim($buffer));
                    preg_match_all("/<([\w]+)[^>]*>.*?<\/\\1>/", $buffer, $matches, PREG_SET_ORDER);
                    $xmlstring = "<?xml version='1.0'?>\n" . $matches[0][0];

                    $xmlobject = simplexml_load_string($xmlstring);

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
                                $kepulanganSiswa = $em->getRepository('LanggasSisdikBundle:KepulanganSiswa')
                                    ->findOneBy([
                                        'sekolah' => $sekolah,
                                        'tahunAkademik' => $jadwal->getTahunAkademik(),
                                        'kelas' => $jadwal->getKelas(),
                                        'siswa' => $siswa,
                                        'tanggal' => $waktuSekarang,
                                        'permulaan' => true,
                                    ])
                                ;
                                if (is_object($kepulanganSiswa) && $kepulanganSiswa instanceof KepulanganSiswa) {
                                    /**$retval['pesan'][] = "··· Memperbarui kepulangan siswa "
                                        . $kepulanganSiswa->getSiswa()->getNamaLengkap()
                                        . " ("
                                        . $kepulanganSiswa->getSiswa()->getNomorIndukSistem()
                                        . ")"
                                    ;**/
                                    $counterJumlahTerproses++;

                                    $kepulanganSiswa->setPermulaan(false);
                                    $kepulanganSiswa->setStatusKepulangan($jadwal->getStatusKepulangan());
                                    $kepulanganSiswa->setJam($logTanggal->format('H:i:s'));

                                    $em->persist($kepulanganSiswa);
                                    $em->flush();
                                }
                            }
                        }

                        $prosesKepulanganSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKepulanganSiswa')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'tahunAkademik' => $jadwal->getTahunAkademik(),
                                'kelas' => $jadwal->getKelas(),
                                'tanggal' => $waktuSekarang,
                                'berhasilDiperbaruiMesin' => false,
                            ])
                        ;

                        if (is_object($prosesKepulanganSiswa) && $prosesKepulanganSiswa instanceof ProsesKepulanganSiswa) {
                            $prosesKepulanganSiswa->setBerhasilDiperbaruiMesin(true);
                            $em->persist($prosesKepulanganSiswa);
                        }
                    }
                }

                @unlink(substr($targetFile, 0, -3));
            }

            $em->flush();

            $retval['pesan'][] = "»»» Jumlah kepulangan siswa terbarui: $counterJumlahTerproses";
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
        $menu[$translator->trans('headings.presence', [], 'navigations')][$translator->trans('links.kepulangan.siswa', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
