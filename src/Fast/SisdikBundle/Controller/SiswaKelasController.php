<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Form\SiswaKelasType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * SiswaKelas controller.
 *
 * @Route("/data/student/{siswa}/class", requirements={"siswa"="\d+"})
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class SiswaKelasController extends Controller
{
    /**
     * Lists all SiswaKelas entities.
     *
     * @Route("/", name="data_studentclass")
     * @Template()
     */
    public function indexAction($siswa) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:SiswaKelas', 't')
                ->leftJoin('t.tahunAkademik', 't2')->leftJoin('t.kelas', 't3')->where('t.siswa = :siswa')
                ->orderBy('t2.urutan', 'DESC')->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.aktif', 'ASC')
                ->setParameter('siswa', $siswa)->getQuery();

        $results = $querybuilder->getResult();

        return array(
            'results' => $results, 'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($siswa)
        );
    }

    /**
     * Displays a form to create a new SiswaKelas entity.
     *
     * @Route("/new", name="data_studentclass_new")
     * @Template()
     */
    public function newAction($siswa) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new SiswaKelas();
        $form = $this->createForm(new SiswaKelasType($this->container, $siswa), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')
                        ->find($siswa)
        );
    }

    /**
     * Creates a new SiswaKelas entity.
     *
     * @Route("/create", name="data_studentclass_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaKelas:new.html.twig")
     */
    public function createAction(Request $request, $siswa) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new SiswaKelas();
        $form = $this->createForm(new SiswaKelasType($this->container, $siswa), $entity);
        $form->bind($request);

        $siswa = $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')->find($siswa);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // permit only one active status in a year
            $aktif = $form->get('aktif')->getData();
            if ($aktif == 1) {
                $obj = $em->getRepository('FastSisdikBundle:SiswaKelas')
                        ->findOneBy(
                                array(
                                        'siswa' => $siswa,
                                        'tahunAkademik' => $form->get('tahunAkademik')->getData()->getId(),
                                        'aktif' => $aktif
                                ));
                if ($obj) {
                    $exception = $this->get('translator')->trans('exception.unique.studentclass.active');
                    throw new \Exception($exception);
                }
            }

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.data.studentclass.inserted',
                                                array(
                                                    '%student%' => $siswa->getNamaLengkap()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_studentclass',
                                                array(
                                                    'siswa' => $siswa
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.studentclass');
                throw new DBALException($exception);
            }
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')
                        ->find($siswa)
        );
    }

    /**
     * Displays a form to edit an existing SiswaKelas entity.
     *
     * @Route("/{id}/edit", name="data_studentclass_edit")
     * @Template()
     */
    public function editAction($siswa, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:SiswaKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaKelasType($this->container, $siswa), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')
                        ->find($siswa)
        );
    }

    /**
     * Edits an existing SiswaKelas entity.
     *
     * @Route("/{id}/update", name="data_studentclass_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaKelas:edit.html.twig")
     */
    public function updateAction(Request $request, $siswa, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:SiswaKelas')->find($id);
        $siswa_entity = $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')
                ->find($siswa);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new SiswaKelasType($this->container, $siswa), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            // permit only one active status in a year
            $aktif = $editForm->get('aktif')->getData();
            if ($aktif == 1) {
                $obj = $em->getRepository('FastSisdikBundle:SiswaKelas')
                        ->findOneBy(
                                array(
                                        'siswa' => $siswa,
                                        'tahunAkademik' => $editForm->get('tahunAkademik')->getData()->getId(),
                                        'aktif' => $aktif
                                ));
                if (is_object($obj) && $obj instanceof SiswaKelas && ($obj->getId() != $entity->getId())) {
                    $exception = $this->get('translator')->trans('exception.unique.studentclass.active');
                    throw new \Exception($exception);
                }
            }

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.data.studentclass.updated',
                                                array(
                                                    '%student%' => $siswa_entity->getNamaLengkap()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_studentclass',
                                                array(
                                                    'siswa' => $siswa
                                                )));

            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.studentclass');
                throw new DBALException($exception);
            }
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')
                        ->find($siswa)
        );
    }

    /**
     * Deletes a SiswaKelas entity.
     *
     * @Route("/{id}/delete", name="data_studentclass_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $siswa, $id) {
        $sekolah = $this->isRegisteredToSchool();

        $siswa = $this->getDoctrine()->getManager()->getRepository('FastSisdikBundle:Siswa')->find($siswa);

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:SiswaKelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.data.studentclass.deleted',
                                            array(
                                                    '%student%' => $siswa->getNamaLengkap(),
                                                    '%class%' => $entity->getKelas()->getNama(),
                                                    '%year%' => $entity->getTahunAkademik()->getNama(),
                                            )));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.data.studentclass.fail.delete',
                                            array(
                                                '%student%' => $siswa->getNamaLengkap()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('data_studentclass',
                                        array(
                                            'siswa' => $siswa
                                        )));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.data.student']->setCurrent(true);
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
