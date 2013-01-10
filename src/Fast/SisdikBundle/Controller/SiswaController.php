<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Util\PasswordGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Entity\Penjurusan;
use Fast\SisdikBundle\Form\SiswaType;
use Fast\SisdikBundle\Form\SiswaSearchType;
use Fast\SisdikBundle\Form\SiswaImportType;
use Fast\SisdikBundle\Form\SiswaKelasImportType;
use Fast\SisdikBundle\Form\SiswaKelasTemplateInitType;
use Fast\SisdikBundle\Form\SiswaKelasTemplateMapType;
use Fast\SisdikBundle\Form\SiswaMergeType;
use Fast\SisdikBundle\Form\SiswaExportType;
use Fast\SisdikBundle\Form\SiswaGenerateUsernameType;
use Fast\SisdikBundle\Util\EasyCSV\Reader;
use Fast\SisdikBundle\Util\EasyCSV\Writer;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Siswa controller.
 *
 * @Route("/data/student")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class SiswaController extends Controller
{
    private $importStudentCount = 0;
    private $importStudentClassCount = 0;
    private $mergeStudentCount = 0;
    const DOCUMENTS_DIR = "/documents/";
    const BASEFILE = "base.ods";
    const OUTPUTPREFIX = "username-";
    const PYCONVERTER = "converter.py";
    const DOCUMENTS_BASEDIR = "base/";
    const DOCUMENTS_OUTPUTDIR = "output/";

    /**
     * Lists all Siswa entities.
     *
     * @Route("/", name="data_student")
     * @Template()
     */
    public function indexAction() {
        $idsekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SiswaSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.idtahunmasuk', 't2')->leftJoin('t.idgelombang', 't3')
                ->where('t2.idsekolah = :idsekolah')->orderBy('t2.tahun DESC, t.namaLengkap');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['idtahunmasuk'] != '') {
                $querybuilder->andWhere('t2.id = :idtahunmasuk');
                $querybuilder->setParameter('idtahunmasuk', $searchdata['idtahunmasuk']);
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder
                        ->andWhere("t.namaLengkap LIKE :searchkey OR t.nomorInduk = :searchkey2");
                $querybuilder->setParameter('searchkey', '%' . $searchdata['searchkey'] . '%');
                $querybuilder->setParameter('searchkey2', $searchdata['searchkey']);
            }
        }

        $querybuilder->setParameter('idsekolah', $idsekolah);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $dlform = $this->createForm(new SiswaExportType($this->container));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'dlform' => $dlform->createView(),
        );
    }

    /**
     * Finds and displays a Siswa entity.
     *
     * @Route("/{id}/show", name="data_student_show")
     * @Template()
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Siswa entity.
     *
     * @Route("/new", name="data_student_new")
     * @Template()
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Siswa();
        $form = $this->createForm(new SiswaType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Creates a new Siswa entity.
     *
     * @Route("/create", name="data_student_create")
     * @Method("post")
     * @Template("FastSisdikBundle:Siswa:new.html.twig")
     */
    public function createAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');

        $entity = new Siswa();
        $request = $this->getRequest();
        $form = $this->createForm(new SiswaType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.studentid.unique');
                throw new DBALException($e);
            }

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.data.student.inserted',
                                            array(
                                                '%student%' => $entity->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('data_student_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));

        }

        return array(
            'entity' => $entity, 'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing Siswa entity.
     *
     * @Route("/{id}/edit", name="data_student_edit")
     * @Template()
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Siswa entity.
     *
     * @Route("/{id}/update", name="data_student_update")
     * @Method("post")
     * @Template("FastSisdikBundle:Siswa:edit.html.twig")
     */
    public function updateAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);
        $prevNomorInduk = $entity->getNomorInduk();

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $editForm = $this->createForm(new SiswaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->bind($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.studentid.unique');
                throw new DBALException($message);
            }

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.data.student.updated',
                                            array(
                                                '%student%' => $entity->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('data_student_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Confirm before really deletes a Siswa entity.
     * Display warning here..
     *
     * @Route("/{id}/deleteconfirm", name="data_student_deleteconfirm")
     * @Method("post")
     * @Template("FastSisdikBundle:Siswa:deleteconfirm.html.twig")
     */
    public function deleteConfirmAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Siswa entity.
     *
     * @Route("/{id}/delete", name="data_student_delete")
     * @Method("post")
     */
    public function deleteAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.student.deleted',
                                                array(
                                                    '%student%' => $entity->getNamaLengkap()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.data.student.fail.delete'));
        }

        return $this->redirect($this->generateUrl('data_student'));
    }

    /**
     * Displays a form to import Siswa entities.
     *
     * @Route("/import/student", name="data_student_import_student")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function importStudentAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaImportType($this->container));

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bind($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $idtahunmasuk = $form['idtahunmasuk']->getData();
                $idgelombang = $form['idgelombang']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this
                            ->importStudent($row, $reader->getHeaders(), $idsekolah, $idtahunmasuk,
                                    $idgelombang);
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentid.unique');
                    throw new DBALException($message);
                } catch (Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.student.imported',
                                                array(
                                                        '%count%' => $this->importStudentCount,
                                                        '%year%' => $idtahunmasuk->getTahun(),
                                                        '%admission%' => $idgelombang->getNama()
                                                )));

                return $this->redirect($this->generateUrl('data_student'));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * Download a csv format file template to import Siswa entities
     *
     * @Route("/download/studenttemplate", name="data_student_student_template")
     */
    public function downloadStudentTemplateAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
        $properties = $reflectionClass->getProperties();

        $filename = "template_data_siswa.csv";

        foreach ($properties as $property) {
            $fieldName = $property->getName();
            if (preg_match('/^id/', $fieldName) || $fieldName === 'file' || $fieldName === 'foto'
                    || $fieldName === 'nomorPendaftaran' || $fieldName === 'nomorIndukSistem'
                    || $fieldName === 'nomorUrutPersekolah')
                continue;
            $fields[] = $fieldName;
        }

        $response = $this
                ->render("FastSisdikBundle:Siswa:$filename.twig",
                        array(
                            'fields' => $fields
                        ));

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

        return $response;
    }

    /**
     * Displays a form to import/map Siswa with SiswaKelas entities.
     *
     * @Route("/import/studentclass", name="data_student_import_studentclass")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function importStudentClassAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new SiswaKelasImportType($this->container));

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bind($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $idtahun = $form['idtahun']->getData();
                $idkelas = $form['idkelas']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this
                            ->importStudentClass($row, $reader->getHeaders(), $idsekolah, $idtahun,
                                    $idkelas);
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentclass.unique');
                    throw new DBALException($message);
                } catch (Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.studentclass.imported',
                                                array(
                                                        '%count%' => $this->importStudentClassCount,
                                                        '%year%' => $idtahun->getNama(),
                                                        '%class%' => $idkelas->getNama()
                                                )));

                return $this->redirect($this->generateUrl('data_student'));
            }
        }

        // form to download template class-student mapping initialization
        $dlform_initialization = $this
                ->createForm(new SiswaKelasTemplateInitType($this->container));

        // form to download template adding class-student mapping
        $dlform_classmap = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        return array(
                'form' => $form->createView(),
                'dlform_initialization' => $dlform_initialization->createView(),
                'dlform_classmap' => $dlform_classmap->createView(),
        );
    }

    /**
     * Download a csv format file template to initialize map Siswa with SiswaKelas entities
     *
     * @Route("/download/studentclasstemplateinit", name="data_student_studentclass_templateinit")
     * @Method("post")
     */
    public function downloadStudentClassTemplateInitAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateInitType($this->container));

        $filename = "template_kelas_siswa_init.csv";

        $form->bind($this->getRequest());

        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $idtahunmasuk = $form->get('idtahunmasuk')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahun masuk yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Siswa', 't')->where('t.idtahunmasuk = :idtahunmasuk')
                    ->andWhere('t.idsekolah = :idsekolah');
            $querybuilder->setParameter('idtahunmasuk', $idtahunmasuk);
            $querybuilder->setParameter('idsekolah', $idsekolah);

            $results = $querybuilder->getQuery()->getResult();

            $students = array();
            foreach ($results as $result) {
                $students[] = array(
                        $result->getNomorIndukSistem(), $result->getNomorInduk(),
                        $result->getNamaLengkap(), $result->getJenisKelamin(), '', 1
                );
            }

            $fields = array(
                    'nomorIndukSistem', 'nomorInduk', 'namaLengkap', 'jenisKelamin', 'kodeJurusan',
                    'aktif', 'keterangan'
            );

            // ambil data kode jurusan
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Penjurusan', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.root ASC, t.lft', 'ASC')->setParameter('idsekolah', $idsekolah);
            $placements = $querybuilder->getQuery()->getResult();

            $response = $this
                    ->render("FastSisdikBundle:Siswa:$filename.twig",
                            array(
                                    'fields' => $fields, 'students' => $students,
                                    'placements' => $placements
                            ));

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

            return $response;
        }
    }

    /**
     * Download a csv format file template to import/map Siswa with SiswaKelas entities
     *
     * @Route("/download/studentclasstemplatemap", name="data_student_studentclass_templatemap")
     */
    public function downloadStudentClassTemplateMapAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        $filename = "template_kelas_siswa_perjenjang.csv";

        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $idtahun = $form->get('idtahun')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahun masuk yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:SiswaKelas', 't')->leftJoin('t.idsiswa', 't2')
                    ->leftJoin('t.idkelas', 't3')->where('t.idtahun = :idtahun')
                    ->andWhere('t2.idsekolah = :idsekolah')->orderBy('t3.kode, t2.nomorInduk');
            $querybuilder->setParameter('idtahun', $idtahun);
            $querybuilder->setParameter('idsekolah', $idsekolah);

            $results = $querybuilder->getQuery()->getResult();

            $students = array();
            foreach ($results as $result) {
                $penjurusan = $result->getIdpenjurusan();
                $kodepenjurusan = (is_object($penjurusan) && $penjurusan instanceof Penjurusan) ? $penjurusan
                                ->getKode() : '';
                $students[] = array(
                        $result->getIdsiswa()->getNomorIndukSistem(),
                        $result->getIdsiswa()->getNomorInduk(),
                        $result->getIdsiswa()->getNamaLengkap(),
                        $result->getIdsiswa()->getJenisKelamin(), $result->getIdkelas()->getKode(),
                        $kodepenjurusan, $result->getAktif(), $result->getKeterangan(),
                );
            }

            $fields = array(
                    'nomorIndukSistem', 'nomorInduk', 'namaLengkap', 'jenisKelamin', 'kodeKelas',
                    'kodeJurusan', 'aktif', 'keterangan'
            );

            // data kodeKelas
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Kelas', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.urutan', 'ASC')->setParameter('idsekolah', $idsekolah);
            $classes = $querybuilder->getQuery()->getResult();

            // data kodeJurusan
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Penjurusan', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.root ASC, t.lft', 'ASC')->setParameter('idsekolah', $idsekolah);
            $placements = $querybuilder->getQuery()->getResult();

            $response = $this
                    ->render("FastSisdikBundle:Siswa:$filename.twig",
                            array(
                                    'fields' => $fields, 'students' => $students,
                                    'classes' => $classes, 'placements' => $placements
                            ));

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

            return $response;
        }
    }

    /**
     * Displays a form to import and merge Siswa entities.
     *
     * @Route("/merge/student", name="data_student_merge_student")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function mergeStudentAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaMergeType());

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bind($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this->mergeStudent($row, $reader->getHeaders());
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentid.unique');
                    throw new DBALException($message);
                } catch (Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.student.merged',
                                                array(
                                                    '%count%' => $this->mergeStudentCount,
                                                )));

                return $this->redirect($this->generateUrl('data_student'));
            }
        }

        $dlform = $this->createForm(new SiswaExportType($this->container));

        return array(
            'form' => $form->createView(), 'dlform' => $dlform->createView()
        );
    }

    /**
     * Download a csv format file template to merge Siswa entities
     *
     * @Route("/download/basicdata", name="data_student_download_basicdata")
     * @Method("POST")
     */
    public function downloadBasicStudentDataAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaExportType($this->container));

        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $filename = "data_siswa_pertahun.csv";

            $idtahunmasuk = $form->get('idtahunmasuk')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahun masuk yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Siswa', 't')->where('t.idtahunmasuk = :idtahunmasuk')
                    ->andWhere('t.idsekolah = :idsekolah');
            $querybuilder->setParameter('idtahunmasuk', $idtahunmasuk);
            $querybuilder->setParameter('idsekolah', $idsekolah);

            $results = $querybuilder->getQuery()->getResult();

            $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
            $properties = $reflectionClass->getProperties();

            foreach ($properties as $property) {
                $fieldName = $property->getName();
                if (preg_match('/^id/', $fieldName) || $fieldName === 'file'
                        || $fieldName === 'foto' || $fieldName === 'nomorPendaftaran'
                        || $fieldName === 'nomorUrutPersekolah')
                    continue;
                $fields[] = $fieldName;
            }

            $students = array();
            foreach ($results as $result) {
                reset($fields);
                unset($tmpdata);
                foreach ($fields as $field) {
                    $tmpdata[] = $result->{'get' . ucfirst($field)}();
                }
                $students[] = $tmpdata;
            }

            $response = $this
                    ->render("FastSisdikBundle:Siswa:$filename.twig",
                            array(
                                'fields' => $fields, 'students' => $students,
                            ));

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);

            return $response;
        }
    }

    /**
     * Generate student usernames
     *
     * @Route("/generate/username", name="data_student_generate_username")
     * @Template("FastSisdikBundle:Siswa:generate.username.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function generateUsernameAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaGenerateUsernameType($this->container));

        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $userManager = $this->container->get('fos_user.user_manager');

                $data = $form->getData();

                $passwordargs = array(
                        'length' => 8, 'alpha_upper_include' => TRUE,
                        'alpha_lower_include' => TRUE, 'number_include' => TRUE,
                        'symbol_include' => TRUE,
                );

                // get siswa for the selected year and class
                $querybuilder = $em->createQueryBuilder()->select('t')
                        ->from('FastSisdikBundle:SiswaKelas', 't')->leftJoin('t.idtahun', 't2')
                        ->leftJoin('t.idkelas', 't3')->leftJoin('t.idsiswa', 't4')
                        ->where('t.idtahun = :idtahun')->andWhere('t.idkelas = :idkelas')
                        ->andWhere('t.aktif = :aktif')->orderBy('t4.nomorIndukSistem', 'ASC')
                        ->setParameter('idtahun', $data['idtahun']->getId())
                        ->setParameter('idkelas', $data['idkelas']->getId())
                        ->setParameter('aktif', TRUE);
                $results = $querybuilder->getQuery()->getResult();

                $output = array();
                foreach ($results as $result) {
                    $siswa = $result->getIdsiswa();
                    if (is_object($siswa) && $siswa instanceof Siswa) {
                        $passwordobject = new PasswordGenerator($passwordargs);

                        $output[] = array(
                                'nama' => $siswa->getNamaLengkap(),
                                'username' => $siswa->getNomorIndukSistem(),
                                'password' => $passwordobject->getPassword()
                        );

                        $user = $userManager->createUser();
                        $user->setUsername($siswa->getNomorIndukSistem());
                        $user->setPlainPassword($passwordobject->getPassword());
                        $user
                                ->setEmail(
                                        $siswa->getNomorIndukSistem() . '-' . $siswa->getEmail());
                        $user->setName($siswa->getNamaLengkap());
                        $user->addRole('ROLE_SISWA');
                        $user->setIdsiswa($siswa);
                        $user->setIdsekolah($siswa->getIdsekolah());
                        $user->setConfirmationToken(null);
                        $user->setEnabled(true);

                        $userManager->updateUser($user);
                    }
                }

                // base
                $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_DIR
                        . self::DOCUMENTS_BASEDIR . self::BASEFILE;

                // source and target
                $extensionsource = ".ods";
                $extensiontarget = "." . $data['output'];

                $filenameoutput = self::OUTPUTPREFIX
                        . preg_replace('/\s+/', '', strtolower($data['idtahun']->getNama())) . '-'
                        . preg_replace('/\s+/', '', strtolower($data['idkelas']->getNama()));

                $filesource = $filenameoutput . $extensionsource;
                $filetarget = $filenameoutput . $extensiontarget;

                $documentsource = $this->get('kernel')->getRootDir() . self::DOCUMENTS_DIR
                        . self::DOCUMENTS_OUTPUTDIR . $filesource;
                $documenttarget = $this->get('kernel')->getRootDir() . self::DOCUMENTS_DIR
                        . self::DOCUMENTS_OUTPUTDIR . $filetarget;

                if ($data['output'] == 'ods') {
                    // do not convert

                    if (copy($documentbase, $documenttarget) === TRUE) {
                        $ziparchive = new \ZipArchive();
                        $ziparchive->open($documenttarget);
                        $ziparchive
                                ->addFromString('content.xml',
                                        $this
                                                ->renderView(
                                                        "FastSisdikBundle:Siswa:username.xml.twig",
                                                        array(
                                                            'users' => $output,
                                                        )));
                        if ($ziparchive->close() === TRUE) {
                            $response = new Response(file_get_contents($documenttarget), 200);
                            $d = $response->headers
                                    ->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                                            $filetarget);
                            $response->headers->set('Content-Disposition', $d);
                            $response->headers->set('Content-Description', 'File Transfer');
                            $response->headers
                                    ->set('Content-Type',
                                            'application/vnd.oasis.opendocument.spreadsheet');
                            $response->headers->set('Content-Transfer-Encoding', 'binary');
                            $response->headers->set('Expires', '0');
                            $response->headers->set('Cache-Control', 'must-revalidate');
                            $response->headers->set('Pragma', 'public');
                            $response->headers->set('Content-Length', filesize($documenttarget));

                            return $response;
                        }
                    }
                } else {
                    // convert from ods to target

                    if (copy($documentbase, $documentsource) === TRUE) {
                        $ziparchive = new \ZipArchive();
                        $ziparchive->open($documentsource);
                        $ziparchive
                                ->addFromString('content.xml',
                                        $this
                                                ->renderView(
                                                        "FastSisdikBundle:Siswa:username.xml.twig",
                                                        array(
                                                            'users' => $output,
                                                        )));
                        if ($ziparchive->close() === TRUE) {
                            $scriptlocation = $this->get('kernel')->getRootDir()
                                    . self::DOCUMENTS_DIR . self::PYCONVERTER;
                            exec("python $scriptlocation $documentsource $documenttarget");

                            $response = new Response(file_get_contents($documenttarget), 200);
                            $d = $response->headers
                                    ->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                                            $filetarget);
                            $response->headers->set('Content-Disposition', $d);
                            $response->headers->set('Content-Description', 'File Transfer');
                            $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                            $response->headers->set('Content-Transfer-Encoding', 'binary');
                            $response->headers->set('Expires', '0');
                            $response->headers->set('Cache-Control', 'must-revalidate');
                            $response->headers->set('Pragma', 'public');
                            $response->headers->set('Content-Length', filesize($documenttarget));

                            return $response;
                        }
                    }
                }
            }
        }
        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * Check if students username and password has already generated
     *
     * @Route("/ajax/generatedusername", name="data_student_ajax_generated_username")
     * @Method("GET")
     */
    public function ajaxGeneratedUsername(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $idtahun = $this->getRequest()->query->get('idtahun');
        $idkelas = $this->getRequest()->query->get('idkelas');

        $query = $em->createQuery('SELECT COUNT(u.id) FROM FastSisdikBundle:SiswaKelas u');
        $count = $query->getSingleScalarResult();
        
        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.idjenjang', 't2')->where('t.idsekolah = :idsekolah')
                ->andWhere('t.idtahun = :idtahun')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('idsekolah', $idsekolah)
                ->setParameter('idtahun', $idtahun);
        $results = $querybuilder->getQuery()->getResult();

        $retval = "";
        foreach ($results as $result) {
            $retval[] = array(
                    'optionValue' => $result->getId(), 'optionDisplay' => $result->getNama(),
                    'optionSelected' => $idkelas == $result->getId() ? 'selected' : ''
            );
        }

        $return = json_encode($retval);
        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    private function mergeStudent($row, $headers, $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

        // find an entity
        $entity = $em->getRepository('FastSisdikBundle:Siswa')
                ->findOneBy(
                        array(
                            'nomorIndukSistem' => $row['NomorIndukSistem']
                        ));
        if (!$entity) {
            return true;
        }

        $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $fieldName = $property->getName();

            if ($fieldName === 'id' || $fieldName == 'nomorIndukSistem') {
                continue;
            }

            $key = array_search(ucfirst($fieldName), $headers);
            if (is_int($key)) {
                if (array_key_exists($headers[$key], $row)) {

                    $value = $row[$headers[$key]];
                    if ($value == "0")
                        $value = null;

                    if ($fieldName == 'tanggalLahir') {
                        if ($value) {
                            $entity->{'set' . ucfirst($fieldName)}(new \DateTime($value));
                        }
                    } else {
                        $entity->{'set' . ucfirst($fieldName)}(trim($value));
                    }
                }
            }
        }

        $em->persist($entity);

        $this->mergeStudentCount++;

        if ($andFlush) {
            $em->flush();
            $em->clear($entity);
        }

    }

    private function importStudent($row, $headers, $idsekolah, $idtahunmasuk, $idgelombang,
            $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

        // Create new entity
        $entity = new Siswa();

        $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $fieldName = $property->getName();

            if ($fieldName === 'id') {
                continue;
            }

            $key = array_search(ucfirst($fieldName), $headers);
            if (is_int($key)) {
                if (array_key_exists($headers[$key], $row)) {

                    $value = $row[$headers[$key]];
                    if ($value == "0")
                        $value = null;

                    if ($fieldName == 'tanggalLahir') {
                        if ($value) {
                            $entity->{'set' . ucfirst($fieldName)}(new \DateTime($value));
                        }
                    } else {
                        $entity->{'set' . ucfirst($fieldName)}(trim($value));
                    }
                    $entity->setIdsekolah($idsekolah);
                    $entity->setIdtahunmasuk($idtahunmasuk);
                    $entity->setIdgelombang($idgelombang);
                    // print '$entity->set' . ucfirst($fieldName) . '(' . trim($value) . ")\n";
                }
            }
        }

        $em->persist($entity);

        $this->importStudentCount++;

        if ($andFlush) {
            $em->flush();
            $em->clear($entity);
        }
    }

    private function importStudentClass($row, $headers, $idsekolah, $idtahun, $idkelas,
            $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

        // Create new siswakelas entity
        $siswakelas = new SiswaKelas();

        $key = array_search('NomorIndukSistem', $headers);
        if (is_int($key)) {
            $student = $em->getRepository('FastSisdikBundle:Siswa')
                    ->findOneBy(
                            array(
                                    'nomorIndukSistem' => $row[$headers[$key]],
                                    'idsekolah' => $idsekolah
                            ));

            if (!$student) {
                throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
            }

            $siswakelas->setIdsiswa($student);
        }

        $siswakelas->setIdtahun($idtahun);
        $siswakelas->setIdkelas($idkelas);

        $key = array_search('KodeJurusan', $headers);
        if (is_int($key)) {
            $placement = $em->getRepository('FastSisdikBundle:Penjurusan')
                    ->findOneBy(
                            array(
                                'kode' => $row[$headers[$key]], 'idsekolah' => $idsekolah
                            ));

            if (!$placement) {
                // allow null
                // throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
            } else {
                $siswakelas->setIdpenjurusan($placement);
            }
        }

        $key = array_search('Aktif', $headers);
        if (is_int($key)) {
            $siswakelas->setAktif($row[$headers[$key]]);

            if ($student) {
                // permit student to only one active status in a year
                $aktif = $row[$headers[$key]];
                if ($aktif == 1) {
                    $obj = $em->getRepository('FastSisdikBundle:SiswaKelas')
                            ->findOneBy(
                                    array(
                                            'idsiswa' => $student->getId(),
                                            'idtahun' => $idtahun->getId(), 'aktif' => $aktif
                                    ));
                    if ($obj) {
                        $exception = $this->get('translator')
                                ->trans('exception.unique.studentclass.active');
                        throw new \Exception($exception);
                    }
                }
            }
        } else {
            throw $this->createNotFoundException('Status aktif/non-aktif harus ditentukan.');
        }
        $key = array_search('Keterangan', $headers);
        if (is_int($key)) {
            $siswakelas->setKeterangan($row[$headers[$key]]);
        }

        $em->persist($siswakelas);

        $this->importStudentClassCount++;

        if ($andFlush) {
            $em->flush();
            $em->clear($siswakelas);
        }
    }

    private function createProceedDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.data.academic']['links.data.student']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            return $idsekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.useadmin.or.headmaster'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
