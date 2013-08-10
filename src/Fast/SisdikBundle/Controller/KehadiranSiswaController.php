<?php

namespace Fast\SisdikBundle\Controller;
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
     * @Method("POST")
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

        $querybuilder_class = $em->createQueryBuilder()->select('kelas')
                ->from('FastSisdikBundle:Kelas', 'kelas')->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                ->where('kelas.sekolah = :sekolah')->andWhere('tahunAkademik.aktif = :aktif')
                ->orderBy('kelas.kode')->setParameter('sekolah', $sekolah->getId())
                ->setParameter('aktif', TRUE);

        $searchform->submit($this->getRequest());
        $buildparam = NULL;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

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

                $querybuilder_class->andWhere("kelas.tingkat = :tingkat");
                $querybuilder_class->setParameter('tingkat', $searchdata['tingkat']->getId());

                $buildparam['tingkat'] = $searchdata['tingkat']->getId();
            } else {
                $buildparam['tingkat'] = '';
            }
            if ($searchdata['kelas'] != '') {
                $querybuilder->andWhere("kelas.id = :kelas");
                $querybuilder->setParameter('kelas', $searchdata['kelas']->getId());

                $querybuilder_class->andWhere("kelas.id = :kelas");
                $querybuilder_class->setParameter('kelas', $searchdata['kelas']->getId());

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
        }

        $entities = $querybuilder->getQuery()->getResult();
        $classes = $querybuilder_class->getQuery()->getResult();

        $students = $this->createForm(new KehadiranSiswaType($this->container, $buildparam));

        $tahunAkademik = $em->getRepository('FastSisdikBundle:TahunAkademik')
                ->findBy(
                        array(
                            'aktif' => true, 'sekolah' => $sekolah->getId(),
                        ));

        return array(
                'entities' => $entities, 'class_entities' => $classes, 'form' => $students->createView(),
                'searchform' => $searchform->createView(), 'buildparam' => $buildparam,
                'tahunAkademik' => $tahunAkademik,
        );
    }

    /**
     * Edits KehadiranSiswa entities.
     *
     * @Route("/update", name="studentspresence_update")
     * @Method("POST")
     */
    public function updateAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $data = urldecode($request->request->get('data'));

        // process update here..
        $test = '';
        if (preg_match_all("/(\d+)\]=(\d+)/", $data, $matches, PREG_SET_ORDER) !== FALSE) {
            foreach ($matches as $keys => $values) {
                $test .= $values[1] . "=" . $values[2] . ";";

                $entity = $em->getRepository('FastSisdikBundle:KehadiranSiswa')->find($values[1]);
                if (is_object($entity) && $entity instanceof KehadiranSiswa) {
                    $entity
                            ->setStatusKehadiranKepulangan(
                                    $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')
                                            ->find($values[2]));
                    $em->persist($entity);
                }
            }
        }
        $em->flush();

        $return = array(
                "responseCode" => 200,
                "responseText" => $this->get('translator')->trans('flash.presence.student.updated'),
                "data" => $data, "matches" => $matches, "test" => $test
        );

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
