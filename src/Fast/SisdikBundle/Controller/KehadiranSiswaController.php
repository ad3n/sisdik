<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\KalenderPendidikan;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Command\InisiasiKehadiranCommand;
use Fast\SisdikBundle\Form\KehadiranSiswaInisiasiType;
use Fast\SisdikBundle\Entity\ProsesKehadiranSiswa;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Fast\SisdikBundle\Form\KehadiranSiswaType;
use Fast\SisdikBundle\Form\KehadiranSiswaSearchType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * KehadiranSiswa controller.
 *
 * @Route("/studentspresence")
 * @PreAuthorize("hasRole('ROLE_GURU_PIKET') or hasRole('ROLE_GURU')")
 */
class KehadiranSiswaController extends Controller
{
    /**
     * Lists all KehadiranSiswa entities.
     *
     * @Route("/", name="studentspresence")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        $hariIni = new \DateTime();
        $searchform->get('tanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('FastSisdikBundle:TahunAkademik')
                ->findBy(
                        array(
                            'aktif' => true, 'sekolah' => $sekolah->getId(),
                        ));

        return array(
            'searchform' => $searchform->createView(), 'tahunAkademik' => $tahunAkademik
        );
    }

    /**
     * Edit KehadiranSiswa entities in a specific date
     *
     * @Route("/edit", name="studentspresence_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('kehadiran, siswa')
                ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')->leftJoin('kehadiran.kelas', 'kelas')
                ->leftJoin('kehadiran.siswa', 'siswa')->where('kelas.sekolah = :sekolah')
                ->orderBy('kelas.kode')->addOrderBy('siswa.namaLengkap')
                ->setParameter('sekolah', $sekolah->getId());

        $searchform->submit($this->getRequest());
        $buildparam = null;
        $kelas = null;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $kbmAktif = $em->getRepository('FastSisdikBundle:KalenderPendidikan')
                    ->findOneBy(
                            array(
                                    'kbm' => true, 'sekolah' => $sekolah->getId(),
                                    'tanggal' => $searchdata['tanggal']
                            ));
            if (!(is_object($kbmAktif) && $kbmAktif instanceof KalenderPendidikan)) {
                $this->get('session')->getFlashBag()
                        ->add('error',
                                $this->get('translator')->trans('flash.kehadiran.siswa.bukan.hari.kbm.aktif'));

                return $this->redirect($this->generateUrl('studentspresence'));
            }

            if ($searchdata['tanggal'] != '') {
                $querybuilder->andWhere('kehadiran.tanggal = :tanggal');
                $querybuilder->setParameter('tanggal', $searchdata['tanggal']);

                $buildparam['tanggal'] = $searchdata['tanggal']->format('Y-m-d');
            } else {
                $buildparam['tanggal'] = '';
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey");
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

                $kelas = $em->getRepository('FastSisdikBundle:Kelas')->find($searchdata['kelas']->getId());

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

            $tahunAkademik = $em->getRepository('FastSisdikBundle:TahunAkademik')
                    ->findOneBy(
                            array(
                                'aktif' => true, 'sekolah' => $sekolah->getId(),
                            ));

            $prosesKehadiranSiswa = null;
            $prosesKehadiranSiswa = $em->getRepository('FastSisdikBundle:ProsesKehadiranSiswa')
                    ->findOneBy(
                            array(
                                    'sekolah' => $sekolah->getId(),
                                    'tahunAkademik' => $tahunAkademik->getId(), 'kelas' => $kelas->getId(),
                                    'tanggal' => $searchdata['tanggal'],
                            ));

            $formInisiasi = $this
                    ->createForm(
                            new KehadiranSiswaInisiasiType($this->container, $kelas,
                                    $searchdata['tanggal']->format('Y-m-d')));

            return array(
                    'kelas' => $kelas, 'entities' => $entities, 'form' => $students->createView(),
                    'searchform' => $searchform->createView(), 'buildparam' => $buildparam,
                    'tahunAkademik' => $tahunAkademik, 'prosesKehadiranSiswa' => $prosesKehadiranSiswa,
                    'tanggal' => $searchdata['tanggal'], 'formInisiasi' => $formInisiasi->createView(),
            );
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.kehadiran.siswa.pencarian.gagal'));

            return $this->redirect($this->generateUrl('studentspresence'));
        }
    }

    /**
     * Edits KehadiranSiswa entities.
     *
     * @Route("/update", name="studentspresence_update")
     * @Method("POST")
     */
    public function updateAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $data = $request->request->get('fast_sisdikbundle_kehadiransiswatype');

        foreach ($data as $keys => $values) {
            if (preg_match('/_(\d+)$/', $keys, $matches) !== FALSE) {
                $kehadiran = $em->getRepository('FastSisdikBundle:KehadiranSiswa')->find($matches[1]);
                if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
                    $kehadiran->setStatusKehadiran($values);
                    $em->persist($kehadiran);
                }
            }
        }

        if (is_object($kehadiran) && $kehadiran instanceof KehadiranSiswa) {
            $prosesKehadiranSiswa = $em->getRepository('FastSisdikBundle:ProsesKehadiranSiswa')
                    ->findOneBy(
                            array(
                                    'sekolah' => $kehadiran->getSekolah()->getId(),
                                    'tahunAkademik' => $kehadiran->getTahunAkademik()->getId(),
                                    'kelas' => $kehadiran->getKelas()->getId(),
                                    'tanggal' => $kehadiran->getTanggal(),
                            ));
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
        }

        $em->flush();

        $return = array(
                "responseCode" => 200,
                "responseText" => $this->get('translator')->trans('flash.presence.student.updated'),
                "data" => $data, "matches" => $matches,
        );

        $return = json_encode($return);
        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * Edits KehadiranSiswa entities.
     *
     * @Route("/inisiasi/{kelas_id}/{tanggal}", name="studentspresence_inisiasi")
     * @Method("POST")
     */
    public function inisiasiAction($kelas_id, $tanggal) {
        $sekolah = $this->isRegisteredToSchool();
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('FastSisdikBundle:TahunAkademik')
                ->findOneBy(
                        array(
                            'aktif' => true, 'sekolah' => $sekolah->getId(),
                        ));


        $kelas = $em->getRepository('FastSisdikBundle:Kelas')->find($kelas_id);

        $formInisiasi = $this->createForm(new KehadiranSiswaInisiasiType($this->container, $kelas, $tanggal));
        $formInisiasi->submit($this->getRequest());

        if ($formInisiasi->isValid()) {
            $statusKehadiran = $formInisiasi->get('statusKehadiran')->getData();

            $qbKehadiran = $em->createQueryBuilder()->select('kehadiran')
                    ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')
                    ->where('kehadiran.sekolah = :sekolah')
                    ->andWhere('kehadiran.tahunAkademik = :tahunAkademik')
                    ->andWhere('kehadiran.kelas = :kelas')->andWhere('kehadiran.tanggal = :tanggal')
                    ->setParameter('sekolah', $sekolah->getId())
                    ->setParameter('tahunAkademik', $tahunAkademik->getId())->setParameter('kelas', $kelas)
                    ->setParameter('tanggal', $tanggal);
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
                $qbSiswaKelas = $em->createQueryBuilder()->select('siswaKelas')
                        ->from('FastSisdikBundle:SiswaKelas', 'siswaKelas')
                        ->where('siswaKelas.tahunAkademik = :tahunakademik')
                        ->andWhere('siswaKelas.kelas = :kelas')
                        ->setParameter('tahunakademik', $tahunAkademik->getId())
                        ->setParameter('kelas', $kelas->getId());
                $entitiesSiswaKelas = $qbSiswaKelas->getQuery()->getResult();
                foreach ($entitiesSiswaKelas as $siswaKelas) {
                    if (!(is_object($siswaKelas) && $siswaKelas instanceof SiswaKelas)) {
                        continue;
                    }

                    $qbKehadiran = $em->createQueryBuilder()->select('kehadiran')
                            ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')
                            ->where('kehadiran.sekolah = :sekolah')->andWhere('kehadiran.siswa = :siswa')
                            ->andWhere('kehadiran.tanggal = :tanggal')
                            ->setParameter('sekolah', $sekolah->getId())
                            ->setParameter('siswa', $siswaKelas->getSiswa()->getId())
                            ->setParameter('tanggal', $tanggal);
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

                    $prosesKehadiranSiswa = new ProsesKehadiranSiswa();
                    $prosesKehadiranSiswa->setSekolah($sekolah);
                    $prosesKehadiranSiswa->setTahunAkademik($tahunAkademik);
                    $prosesKehadiranSiswa->setKelas($kelas);
                    $prosesKehadiranSiswa->setTanggal(new \DateTime($tanggal));
                    $prosesKehadiranSiswa->setBerhasilInisiasi(true);

                    $em->persist($prosesKehadiranSiswa);
                }
            }

            $em->flush();

            $return = array(
                    "responseCode" => 200,
                    "responseText" => $this->get('translator')->trans('flash.inisiasi.berhasil.dijalankan'),
                    "data" => 'refresh',
            );
        } else {
            $return = array(
                    "responseCode" => 400,
                    "responseText" => $this->get('translator')->trans('flash.inisiasi.gagal.dijalankan'),
                    "data" => 'norefresh',
            );
        }

        $return = json_encode($return);
        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.presence']['links.studentspresence']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
