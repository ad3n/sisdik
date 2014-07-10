<?php

namespace Langgas\SisdikBundle\Controller;
use Langgas\SisdikBundle\Form\ConfirmationType;

use Langgas\SisdikBundle\Form\SiswaApplicantSearchType;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Util\Messenger;
use Langgas\SisdikBundle\Entity\LayananSmsPendaftaran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Form\SiswaApplicantType;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

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
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();
        $searchkey = '';

        $searchform = $this->createForm(new SiswaApplicantSearchType($this->container));

        $qbtotal = $em->createQueryBuilder()->select('COUNT(t.id)')->from('LanggasSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->where('t.calonSiswa = :calon')->setParameter('calon', true)
                ->andWhere('t.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->andWhere('t2.id = ?1')->setParameter(1, $panitiaAktif[2]);
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $qbsearchnum = $em->createQueryBuilder()->select('COUNT(t.id)')->from('LanggasSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = ?1')
                ->setParameter(1, $panitiaAktif[2]);

        $querybuilder = $em->createQueryBuilder()->select('t')->from('LanggasSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->leftJoin('t.sekolahAsal', 't4')
                ->leftJoin('t.orangtuaWali', 'orangtua')->where('t.calonSiswa = :calon')
                ->setParameter('calon', true)->andWhere('orangtua.aktif = :ortuaktif')
                ->setParameter('ortuaktif', true)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('t2.id = ?1')
                ->setParameter(1, $panitiaAktif[2])->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t3.urutan', 'DESC')->addOrderBy('t.nomorUrutPendaftaran', 'DESC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $tampilkanTercari = false;

            if ($searchdata['gelombang'] != '') {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());

                $qbsearchnum->andWhere('t.gelombang = :gelombang');
                $qbsearchnum->setParameter('gelombang', $searchdata['gelombang']->getId());
                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $qbsearchnum
                        ->andWhere(
                                't.namaLengkap LIKE :namalengkap OR t.nomorPendaftaran LIKE :nomor '
                                        . ' OR t.keterangan LIKE :keterangan OR t.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $qbsearchnum->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $qbsearchnum->setParameter('ponselortu', "%{$searchdata['searchkey']}%");

                $tampilkanTercari = true;
            }

        }

        $pendaftarTercari = $qbsearchnum->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'panitiaAktif' => $panitiaAktif, 'pendaftarTotal' => $pendaftarTotal,
                'pendaftarTercari' => $pendaftarTercari, 'tampilkanTercari' => $tampilkanTercari,
                'searchkey' => $searchkey,
        );
    }

    /**
     * Creates a new Siswa applicant entity.
     *
     * @Route("/", name="applicant_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaApplicant:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $entity = new Siswa();
        $form = $this->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'new'), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity->setCalonSiswa(true);

            $qbmaxnum = $em->createQueryBuilder()->select('MAX(siswa.nomorUrutPendaftaran)')
                    ->from('LanggasSisdikBundle:Siswa', 'siswa')->where("siswa.tahun = :tahun")
                    ->setParameter('tahun', $entity->getTahun())->andWhere('siswa.sekolah = :sekolah')
                    ->setParameter('sekolah', $entity->getSekolah());
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());
            $nomormax++;
            $entity->setNomorUrutPendaftaran($nomormax);
            $entity->setNomorPendaftaran($entity->getTahun()->getTahun() . $nomormax);

            if ($form['adaReferensi']->getData() === true && $form['referensi']->getData() === null
                    && $form['namaReferensi']->getData() != "") {
                $referensi = new Referensi();
                $referensi->setNama($form['namaReferensi']->getData());
                $referensi->setSekolah($sekolah);
                $entity->setReferensi($referensi);
            }

            try {
                $em->persist($entity);
                $em->flush();

                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                        ->findBy(
                                array(
                                    'sekolah' => $sekolah, 'jenisLayanan' => 'a-pendaftaran-tercatat',
                                ));

                foreach ($pilihanLayananSms as $pilihan) {
                    if ($pilihan instanceof PilihanLayananSms) {
                        if ($pilihan->getStatus()) {
                            $layananSmsPendaftaran = $em
                                    ->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')
                                    ->findBy(
                                            array(
                                                    'sekolah' => $sekolah,
                                                    'jenisLayanan' => 'a-pendaftaran-tercatat'
                                            ));
                            foreach ($layananSmsPendaftaran as $layanan) {
                                if ($layanan instanceof LayananSmsPendaftaran) {
                                    $tekstemplate = $layanan->getTemplatesms()->getTeks();

                                    $namaOrtuWali = "";
                                    $ponselOrtuWali = "";
                                    foreach ($entity->getOrangtuaWali() as $orangtuaWali) {
                                        if ($orangtuaWali instanceof OrangtuaWali) {
                                            if ($orangtuaWali->isAktif()) {
                                                $namaOrtuWali = $orangtuaWali->getNama();
                                                $ponselOrtuWali = $orangtuaWali->getPonsel();
                                                break;
                                            }
                                        }
                                    }

                                    $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali,
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%nama-calonsiswa%",
                                            $entity->getNamaLengkap(), $tekstemplate);
                                    $tekstemplate = str_replace("%tahun%", $entity->getTahun()->getTahun(),
                                            $tekstemplate);
                                    $tekstemplate = str_replace("%gelombang%",
                                            $entity->getGelombang()->getNama(), $tekstemplate);

                                    if ($ponselOrtuWali != "") {
                                        $nomorponsel = preg_split("/[\s,]+/", $ponselOrtuWali);
                                        foreach ($nomorponsel as $ponsel) {
                                            $messenger = $this->get('sisdik.messenger');
                                            if ($messenger instanceof Messenger) {
                                                $messenger->setPhoneNumber($ponsel);
                                                $messenger->setMessage($tekstemplate);
                                                $messenger->sendMessage($sekolah);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $this->get('session')->getFlashBag()
                        ->add('success',
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
     * Displays a form to create a new Siswa applicant entity.
     *
     * @Route("/new", name="applicant_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $entity = new Siswa();
        $orangtuaWali = new OrangtuaWali();
        $entity->getOrangtuaWali()->add($orangtuaWali);

        $form = $this->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'new'), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a Siswa applicant entity.
     *
     * @Route("/{id}", name="applicant_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(), 'panitiaAktif' => $panitiaAktif,
        );
    }

    /**
     * Displays a form to edit an existing Siswa applicant entity.
     *
     * @Route("/{id}/edit", name="applicant_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this
                ->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'edit'), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Siswa applicant entity.
     *
     * @Route("/{id}", name="applicant_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaApplicant:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this
                ->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'edit'), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {

                if ($editForm['referensi']->getData() === null && $editForm['namaReferensi']->getData() != "") {
                    $referensi = new Referensi();
                    $referensi->setNama($editForm['namaReferensi']->getData());
                    $referensi->setSekolah($sekolah);
                    $entity->setReferensi($referensi);
                }

                if ($editForm['sekolahAsal']->getData() === null
                        && $editForm['namaSekolahAsal']->getData() != "") {
                    $sekolahAsal = new SekolahAsal();
                    $sekolahAsal->setNama($editForm['namaSekolahAsal']->getData());
                    $sekolahAsal->setSekolah($sekolah);
                    $entity->setSekolahAsal($sekolahAsal);
                }

                // force unit of work detect entity 'changes'
                // possible problem source: too many objects handled by doctrine
                $entity->setWaktuUbah(new \DateTime());

                $entity->setDiubahOleh($this->getUser());

                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
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
     * @Route("/webcamupload/{tahun}", name="applicant_webcam_uploadhandler")
     * @Method("POST")
     */
    public function webcamUploadHandlerAction(Request $request, $tahun) {
        $sekolah = $this->isRegisteredToSchool();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $fs = new Filesystem();
        if (!$fs->exists(Siswa::WEBCAMPHOTO_DIR . $sekolah->getId() . '/' . $tahun)) {
            $fs->mkdir(Siswa::WEBCAMPHOTO_DIR . $sekolah->getId() . '/' . $tahun);
        }

        $filename = date('YmdHis') . '.jpg';
        $targetfile = Siswa::WEBCAMPHOTO_DIR . $sekolah->getId() . '/' . $tahun . '/' . $filename;

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
     * @Method("GET")
     * @Template("LanggasSisdikBundle:SiswaApplicant:editregphoto.html.twig")
     */
    public function editRegistrationPhotoAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this
                ->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'editregphoto'),
                        $entity);

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing Siswa applicant entity.
     *
     * @Route("/{id}/updateregphoto", name="applicant_updateregphoto")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaApplicant:editregphoto.html.twig")
     */
    public function updateRegistrationPhotoAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this
                ->createForm(new SiswaApplicantType($this->container, $panitiaAktif[2], 'editregphoto'),
                        $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
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
     * Displays a confirmation form to delete a applicant
     * menghapus hanya bisa dilakukan terhadap data yang belum memiliki data pembayaran
     *
     * @Route("/{id}/remove", name="applicant_delete_confirm")
     * @Template("LanggasSisdikBundle:SiswaApplicant:delete.confirm.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KETUA_PANITIA_PSB")
     */
    public function deleteConfirmAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0]))
                || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);
        if (!$entity && !$entity instanceof Siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if (count($entity->getPembayaranPendaftaran()) > 0) {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')->trans('alert.pendaftar.sudah.melakukan.pembayaran'));
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_show',
                                            array(
                                                'id' => $id,
                                            )));
        }

        $form = $this->createForm(new ConfirmationType($this->container, uniqid()));

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {

                try {
                    $em->remove($entity);
                    $em->flush();

                    $this->get('session')->getFlashBag()
                            ->add('success',
                                    $this->get('translator')
                                            ->trans('flash.applicant.deleted',
                                                    array(
                                                        '%name%' => $entity->getNamaLengkap(),
                                                    )));

                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.delete.restrict');
                    throw new DBALException($message);
                }

                return $this->redirect($this->generateUrl('applicant'));
            } else {
                $this->get('session')->getFlashBag()
                        ->add('error',
                                $this->get('translator')
                                        ->trans('flash.applicant.fail.delete',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap(),
                                                )));

            }
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    /**
     * Mencari panitia pendaftaran aktif
     * Fungsi ini mengembalikan array berisi
     * index 0: daftar id panitia aktif
     * index 1: id ketua panitia aktif
     * index 2: id tahun panitia aktif
     * index 3: string tahun panitia aktif
     *
     * @return array panitiaaktif
     */
    public function getPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('panitia')
                ->from('LanggasSisdikBundle:PanitiaPendaftaran', 'panitia')->leftJoin('panitia.tahun', 'tahun')
                ->where('panitia.sekolah = :sekolah')->andWhere('panitia.aktif = 1')
                ->orderBy('tahun.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())
                ->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        $panitiaaktif = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $panitiaaktif[0] = $entity->getPanitia();
                $panitiaaktif[1] = $entity->getKetuaPanitia()->getId();
                $panitiaaktif[2] = $entity->getTahun()->getId();
                $panitiaaktif[3] = $entity->getTahun()->getTahun();
            }
        }

        return $panitiaaktif;
    }

    private function verifyTahun($tahun) {
        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if ($panitiaAktif[3] != $tahun) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('cannot.alter.applicant.inactive.year'));
        }
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.pendaftaran', array(), 'navigations')][$this->get('translator')->trans('links.registration', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool() {
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