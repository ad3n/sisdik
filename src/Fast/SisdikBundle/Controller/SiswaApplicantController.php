<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaApplicantPaymentSearchType;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Fast\SisdikBundle\Form\SiswaApplicantSearchType;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Form\SiswaApplicantType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa applicant controller.
 *
 * @Route("/applicant")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB')")
 */
class SiswaApplicantController extends Controller
{
    /**
     * Lists all Siswa (applicant only) entities.
     *
     * @Route("/", name="applicant")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.context')->getToken()->getUser();

        $qb1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->leftJoin('t.tahunmasuk', 't2')->where('t2.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId());
        $results = $qb1->getQuery()->getResult();
        $daftarTahunmasuk = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                if ((is_array($entity->getPanitia()) && in_array($user->getId(), $entity->getPanitia()))
                        || $entity->getKetuaPanitia()->getId() == $user->getId()) {
                    $daftarTahunmasuk[] = $entity->getTahunmasuk()->getId();
                }
            }
        }

        if (count($daftarTahunmasuk) == 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $searchform = $this->createForm(new SiswaApplicantSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahunmasuk', 't2')->leftJoin('t.gelombang', 't3')
                ->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t2.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id IN (?1)')->setParameter(1, $daftarTahunmasuk)->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorPendaftaran', 'DESC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :tahunmasuk');
                $querybuilder->setParameter('tahunmasuk', $searchdata['tahunmasuk']->getId());
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('t.namaLengkap LIKE :namalengkap');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
            }

        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'tahunaktif' => $this->getTahunmasukPanitiaAktif(),
        );
    }

    /**
     * Finds and displays a Siswa applicant entity.
     *
     * @Route("/{id}/show", name="applicant_show")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'tahunaktif' => $this->getTahunmasukPanitiaAktif(),
        );
    }

    /**
     * Displays a form to create a new Siswa applicant entity.
     *
     * @Route("/new", name="applicant_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Siswa();
        $orangtuaWali = new OrangtuaWali();
        $entity->getOrangtuaWali()->add($orangtuaWali);

        $form = $this->createForm(new SiswaApplicantType($this->container, 'new'), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Siswa applicant entity.
     *
     * @Route("/create", name="applicant_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaApplicant:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Siswa();
        $form = $this->createForm(new SiswaApplicantType($this->container, 'new'), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity->setDibuatOleh($this->get('security.context')->getToken()->getUser());
            $entity->setCalonSiswa(true);

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.inserted',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap()
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Siswa applicant entity.
     *
     * @Route("/{id}/edit", name="applicant_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        $this->verifyTahunmasuk($entity->getTahunmasuk()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaApplicantType($this->container, 'edit'), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Siswa applicant entity.
     *
     * @Route("/{id}/update", name="applicant_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaApplicant:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        $this->verifyTahunmasuk($entity->getTahunmasuk()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new SiswaApplicantType($this->container, 'edit'), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {

                $entity->setDiubahOleh($this->container->get('security.context')->getToken()->getUser());

                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.updated',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_edit',
                                            array(
                                                'id' => $id, 'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Handling HTTP RAW DATA sent from jpegcam library
     *
     * @Route("/webcamupload", name="applicant_webcam_uploadhandler")
     * @Method("POST")
     */
    public function webcamUploadHandlerAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $fs = new Filesystem();
        if (!$fs->exists(Siswa::WEBCAMPHOTO_DIR . $sekolah->getId())) {
            $fs->mkdir(Siswa::WEBCAMPHOTO_DIR . $sekolah->getId());
        }

        $filename = date('YmdHis') . '.jpg';
        $targetfile = Siswa::WEBCAMPHOTO_DIR . $sekolah->getId() . '/' . $filename;

        $output = $filename;

        $result = file_put_contents($targetfile, file_get_contents('php://input'));
        if (!$result) {
            $output = $this->get('translator')
                    ->trans('errorinfo.cannot.writefile',
                            array(
                                'filename' => $filename
                            ));
        }

        return new Response($output, 200,
                array(
                    'Content-Type' => 'text/plain'
                ));
    }

    /**
     * Displays a form to edit only registration photo of Siswa applicant entity.
     *
     * @Route("/{id}/editregphoto", name="applicant_editregphoto")
     * @Template("FastSisdikBundle:SiswaApplicant:editregphoto.html.twig")
     */
    public function editRegistrationPhotoAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        $this->verifyTahunmasuk($entity->getTahunmasuk()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaApplicantType($this->container, 'editregphoto'), $entity);

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing Siswa applicant entity.
     *
     * @Route("/{id}/updateregphoto", name="applicant_updateregphoto")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaApplicant:editregphoto.html.twig")
     */
    public function updateRegistrationPhotoAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        $this->verifyTahunmasuk($entity->getTahunmasuk()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaApplicantType($this->container, 'editregphoto'), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.regphoto.updated',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_show',
                                            array(
                                                'id' => $id, 'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Deletes a Siswa applicant entity.
     *
     * @Route("/{id}/delete", name="applicant_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

            $this->verifyTahunmasuk($entity->getTahunmasuk()->getTahun());

            if (!$entity) {
                throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.deleted',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')
                                    ->trans('flash.applicant.fail.delete',
                                            array(
                                                '%name%' => $entity->getNamaLengkap(),
                                            )));
        }

        return $this->redirect($this->generateUrl('applicant'));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function getTahunmasukPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->leftJoin('t.tahunmasuk', 't2')->where('t2.sekolah = :sekolah')->andWhere('t.aktif = 1')
                ->orderBy('t2.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $tahunaktif = $entity->getTahunmasuk()->getTahun();
            }
        }

        return $tahunaktif;
    }

    private function verifyTahunmasuk($tahun) {
        if ($this->getTahunmasukPanitiaAktif() != $tahun) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('cannot.alter.applicant.inactive.yearentry'));
        }
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.registration']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
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
