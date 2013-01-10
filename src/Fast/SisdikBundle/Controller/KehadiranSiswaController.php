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
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        return array(
            'searchform' => $searchform->createView()
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
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KehadiranSiswaSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t, t3')
                ->from('FastSisdikBundle:KehadiranSiswa', 't')->leftJoin('t.idkelas', 't2')
                ->leftJoin('t.idsiswa', 't3')->where('t2.idsekolah = :idsekolah')
                ->orderBy('t2.kode')->addOrderBy('t3.namaLengkap')
                ->setParameter('idsekolah', $idsekolah);

        $querybuilder_class = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.idtahun', 't2')
                ->where('t.idsekolah = :idsekolah')->andWhere('t2.aktif = :aktif')
                ->orderBy('t.kode')->setParameter('idsekolah', $idsekolah)
                ->setParameter('aktif', TRUE);

        $searchform->bind($this->getRequest());
        $buildparam = NULL;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tanggal'] != '') {
                $querybuilder->andWhere('t.tanggal = :tanggal');
                $querybuilder->setParameter('tanggal', $searchdata['tanggal']);

                $buildparam['tanggal'] = $searchdata['tanggal']->format('Y-m-d');
            } else {
                $buildparam['tanggal'] = '';
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere(
                                "t3.namaLengkap LIKE :searchkey OR t3.nomorInduk LIKE :searchkey");
                $querybuilder->setParameter('searchkey', '%' . $searchdata['searchkey'] . '%');

                $buildparam['searchkey'] = $searchdata['searchkey'];
            } else {
                $buildparam['searchkey'] = '';
            }
            if ($searchdata['idjenjang'] != '') {
                $querybuilder->andWhere("t2.idjenjang = :idjenjang");
                $querybuilder->setParameter('idjenjang', $searchdata['idjenjang']->getId());

                $querybuilder_class->andWhere("t.idjenjang = :idjenjang");
                $querybuilder_class->setParameter('idjenjang', $searchdata['idjenjang']->getId());

                $buildparam['idjenjang'] = $searchdata['idjenjang']->getId();
            } else {
                $buildparam['idjenjang'] = '';
            }
            if ($searchdata['idkelas'] != '') {
                $querybuilder->andWhere("t2.id = :idkelas");
                $querybuilder->setParameter('idkelas', $searchdata['idkelas']->getId());

                $querybuilder_class->andWhere("t.id = :idkelas");
                $querybuilder_class->setParameter('idkelas', $searchdata['idkelas']->getId());

                $buildparam['idkelas'] = $searchdata['idkelas']->getId();
            } else {
                $buildparam['idkelas'] = '';
            }
            if ($searchdata['idstatuskehadirankepulangan'] != '') {
                $querybuilder
                        ->andWhere("t.idstatusKehadiranKepulangan = :idstatuskehadirankepulangan");
                $querybuilder
                        ->setParameter('idstatuskehadirankepulangan',
                                $searchdata['idstatuskehadirankepulangan']->getId());

                $buildparam['idstatuskehadirankepulangan'] = $searchdata['idstatuskehadirankepulangan']
                        ->getId();
            } else {
                $buildparam['idstatuskehadirankepulangan'] = '';
            }
        }

        $entities = $querybuilder->getQuery()->getResult();
        $classes = $querybuilder_class->getQuery()->getResult();

        $students = $this->createForm(new KehadiranSiswaType($this->container, $buildparam));

        return array(
                'entities' => $entities, 'class_entities' => $classes,
                'form' => $students->createView(), 'searchform' => $searchform->createView(),
                'buildparam' => $buildparam
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
                            ->setIdstatusKehadiranKepulangan(
                                    $em
                                            ->getRepository(
                                                    'FastSisdikBundle:StatusKehadiranKepulangan')
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            return $idsekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
