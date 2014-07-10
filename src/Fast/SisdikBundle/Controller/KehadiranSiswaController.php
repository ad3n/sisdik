<?php
namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Form\KehadiranSiswaSmsType;
use Langgas\SisdikBundle\Form\KehadiranSiswaInisiasiType;
use Langgas\SisdikBundle\Form\KehadiranSiswaType;
use Langgas\SisdikBundle\Form\KehadiranSiswaSearchType;
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
use Langgas\SisdikBundle\Util\Messenger;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/kehadiran-siswa")
 * @PreAuthorize("hasRole('ROLE_GURU_PIKET') or hasRole('ROLE_GURU')")
 */
class KehadiranSiswaController extends Controller
{
    /**
     * @Route("/", name="kehadiran-siswa")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        $hariIni = new \DateTime();
        $searchform->get('tanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        return [
            'searchform' => $searchform->createView(),
            'tahunAkademik' => $tahunAkademik,
        ];
    }

    /**
     * @Route("/edit", name="kehadiran-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()
            ->select('kehadiran')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah->getId())
        ;

        $searchform->submit($this->getRequest());
        $buildparam = null;
        $kelas = null;

        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $kbmAktif = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')
                ->findOneBy([
                    'kbm' => true,
                    'sekolah' => $sekolah->getId(),
                    'tanggal' => $searchdata['tanggal']
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

            if ($searchdata['tanggal'] != '') {
                $querybuilder->andWhere('kehadiran.tanggal = :tanggal');
                $querybuilder->setParameter('tanggal', $searchdata['tanggal']);

                $buildparam['tanggal'] = $searchdata['tanggal']->format('Y-m-d');
            } else {
                $buildparam['tanggal'] = '';
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey");
                $querybuilder->setParameter('searchkey', "%{$searchdata['searchkey']}%");

                $buildparam['searchkey'] = $searchdata['searchkey'];
            } else {
                $buildparam['searchkey'] = '';
            }

            if ($searchdata['tingkat'] != '') {
                $querybuilder->andWhere("kelas.tingkat = :tingkat");
                $querybuilder->setParameter('tingkat', $searchdata['tingkat']->getId());

                $buildparam['tingkat'] = $searchdata['tingkat']->getId();
            } else {
                $buildparam['tingkat'] = '';
            }

            if ($searchdata['kelas'] != '') {
                $querybuilder->andWhere("kelas.id = :kelas");
                $querybuilder->setParameter('kelas', $searchdata['kelas']->getId());

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

            $students = $this->createForm(new KehadiranSiswaType($this->container, $buildparam));

            $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
                ->findOneBy([
                    'aktif' => true,
                    'sekolah' => $sekolah->getId(),
                ])
            ;

            $prosesKehadiranSiswa = null;
            $prosesKehadiranSiswa = $em->getRepository('LanggasSisdikBundle:ProsesKehadiranSiswa')
                ->findOneBy([
                    'sekolah' => $sekolah->getId(),
                    'tahunAkademik' => $tahunAkademik->getId(),
                    'kelas' => $kelas->getId(),
                    'tanggal' => $searchdata['tanggal'],
                ])
            ;

            $formInisiasi = $this->createForm(new KehadiranSiswaInisiasiType($this->container, $kelas, $searchdata['tanggal']->format('Y-m-d')));
            $formSms = $this->createForm(new KehadiranSiswaSmsType($this->container, $kelas, $searchdata['tanggal']->format('Y-m-d'), $entities));

            return [
                'kelas' => $kelas,
                'entities' => $entities,
                'form' => $students->createView(),
                'searchform' => $searchform->createView(),
                'buildparam' => $buildparam,
                'tahunAkademik' => $tahunAkademik,
                'prosesKehadiranSiswa' => $prosesKehadiranSiswa,
                'tanggal' => $searchdata['tanggal'],
                'formInisiasi' => $formInisiasi->createView(),
                'formSms' => $formSms->createView(),
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
     * Memperbarui kehadiran siswa
     *
     * @Route("/update", name="kehadiran-siswa_update")
     * @Method("POST")
     */
    public function updateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->get('sisdik_kehadiransiswa');

        foreach ($data as $keys => $values) {
            if (preg_match('/kehadiran_(\d+)$/', $keys, $matches) !== FALSE) {
                if (array_key_exists(1, $matches)) {
                    $kehadiran = $em->getRepository('LanggasSisdikBundle:KehadiranSiswa')->find($matches[1]);
                    if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                        $kehadiran->setStatusKehadiran($values);
                        $kehadiran->setKeteranganStatus($data['kehadiran_keterangan_' . $matches[1]]);
                        $em->persist($kehadiran);
                    }
                }
            }
        }

        $return = array();
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
     * Menginisiasi kehadiran siswa
     *
     * @Route("/inisiasi/{kelas_id}/{tanggal}", name="kehadiran-siswa_inisiasi")
     * @Method("POST")
     */
    public function inisiasiAction($kelas_id, $tanggal)
    {
        $sekolah = $this->isRegisteredToSchool();
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        $kelas = $em->getRepository('LanggasSisdikBundle:Kelas')->find($kelas_id);

        $formInisiasi = $this->createForm(new KehadiranSiswaInisiasiType($this->container, $kelas, $tanggal));
        $formInisiasi->submit($this->getRequest());

        if ($formInisiasi->isValid()) {
            $statusKehadiran = $formInisiasi->get('statusKehadiran')->getData();

            $qbKehadiran = $em->createQueryBuilder()
                ->select('kehadiran')
                ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
                ->where('kehadiran.sekolah = :sekolah')
                ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
                ->andWhere('kehadiran.kelas = :kelas')
                ->andWhere('kehadiran.tanggal = :tanggal')
                ->setParameter('sekolah', $sekolah->getId())
                ->setParameter('tahunAkademik', $tahunAkademik->getId())
                ->setParameter('kelas', $kelas)
                ->setParameter('tanggal', $tanggal)
            ;
            $entities = $qbKehadiran->getQuery()->getResult();
            if (count($entities) > 0) {
                foreach ($entities as $kehadiran) {
                    if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                        $kehadiran->setKeteranganStatus(null);
                        $kehadiran->setPermulaan(true);
                        $kehadiran->setSmsDlr(null);
                        $kehadiran->setSmsDlrtime(null);
                        $kehadiran->setSmsTerproses(false);
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
                    ->setParameter('tahunakademik', $tahunAkademik->getId())
                    ->setParameter('kelas', $kelas->getId())
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
                        ->setParameter('sekolah', $sekolah->getId())
                        ->setParameter('siswa', $siswaKelas->getSiswa()->getId())
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
                    $kehadiran->setTanggal(new \DateTime($tanggal));
                    $jam = new \DateTime();
                    $kehadiran->setJam($jam->format('H:i') . ':00');
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
     * Mengirim SMS kehadiran
     *
     * @Route("/kirim-sms/{kelas_id}/{tanggal}", name="kehadiran-siswa_kirimsms")
     * @Method("POST")
     */
    public function kirimSmsAction($kelas_id, $tanggal)
    {
        $sekolah = $this->isRegisteredToSchool();
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

        $qbKehadiranSiswa = $em->createQueryBuilder()
            ->select('kehadiran')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->where('kehadiran.sekolah = :sekolah')
            ->andWhere('kehadiran.kelas = :kelas')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('kelas', $kelas)
        ;
        $kehadiranSiswa = $qbKehadiranSiswa->getQuery()->getResult();

        $formKirimSms = $this->createForm(new KehadiranSiswaSmsType($this->container, $kelas, $tanggal, $kehadiranSiswa));
        $formKirimSms->submit($this->getRequest());

        if ($formKirimSms->isValid()) {
            $statusKehadiran = $formKirimSms->get('statusKehadiran')->getData();
            $siswa = $formKirimSms->get('siswa')->getData();

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
                $return['responseText'] = "tidak ada jadwal kehadiran yang sesuai";
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
                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWaliAktif);
                        foreach ($nomorponsel as $ponsel) {
                            $messenger = $this->get('sisdik.messenger');
                            if ($messenger instanceof Messenger) {
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
                                $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWaliAktif);
                                foreach ($nomorponsel as $ponsel) {
                                    $messenger = $this->get('sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
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
            $return['responseText'] = "form tidak valid";
            $return = json_encode($return);

            return new Response($return, 200, ['Content-Type' => 'application/json']);
        }
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', array(), 'navigations')][$this->get('translator')->trans('links.kehadiran.siswa', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool()
    {
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
