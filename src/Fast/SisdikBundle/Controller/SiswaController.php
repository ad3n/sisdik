<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
use Fast\SisdikBundle\Util\EasyCSV\Reader;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Siswa controller.
 *
 * @Route("/siswa")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class SiswaController extends Controller
{
    private $importStudentCount = 0;
    private $importStudentClassCount = 0;
    private $mergeStudentCount = 0;

    /**
     * Lists all Siswa entities.
     *
     * @Route("/", name="data_student")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SiswaSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->where('t.calonSiswa = :calon')
                ->setParameter('calon', false)->andWhere('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->orderBy('t2.tahun', 'DESC')
                ->addOrderBy('t.namaLengkap', 'ASC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t2.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }
            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere("t.namaLengkap LIKE :searchkey OR t.nomorInduk = :searchkey2");
                $querybuilder->setParameter('searchkey', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('searchkey2', $searchdata['searchkey']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

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
        );
    }

    /**
     * Displays a form to create a new Siswa entity.
     *
     * @Route("/new", name="data_student_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');

        $entity = new Siswa();
        $request = $this->getRequest();
        $form = $this->createForm(new SiswaType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.studentid.unique');
                throw new DBALException($e);
            }

            $this->get('session')->getFlashBag()
                    ->add('success',
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
        $sekolah = $this->isRegisteredToSchool();
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
        $sekolah = $this->isRegisteredToSchool();
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

        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.studentid.unique');
                throw new DBALException($message);
            }

            $this->get('session')->getFlashBag()
                    ->add('success',
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
        $this->isRegisteredToSchool();
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
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
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
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.data.student.fail.delete'));
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaImportType($this->container));

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->submit($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $tahun = $form['tahun']->getData();
                $gelombang = $form['gelombang']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this->importStudent($row, $reader->getHeaders(), $sekolah, $tahun, $gelombang);
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

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.data.student.imported',
                                                array(
                                                        '%count%' => $this->importStudentCount,
                                                        '%year%' => $tahun->getTahun(),
                                                        '%admission%' => $gelombang->getNama()
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
        $sekolah = $this->isRegisteredToSchool();
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new SiswaKelasImportType($this->container));

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->submit($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $tahunAkademik = $form['tahunAkademik']->getData();
                $kelas = $form['kelas']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this->importStudentClass($row, $reader->getHeaders(), $sekolah, $tahunAkademik, $kelas);
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

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.data.studentclass.imported',
                                                array(
                                                        '%count%' => $this->importStudentClassCount,
                                                        '%year%' => $tahunAkademik->getNama(),
                                                        '%class%' => $kelas->getNama()
                                                )));

                return $this->redirect($this->generateUrl('data_student'));
            }
        }

        // form to download template class-student mapping initialization
        $dlform_initialization = $this->createForm(new SiswaKelasTemplateInitType($this->container));

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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateInitType($this->container));

        $filename = "template_kelas_siswa_init.csv";

        $form->submit($this->getRequest());

        if ($form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $tahun = $form->get('tahun')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahun masuk yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                    ->where('t.tahun = :tahun')->andWhere('t.sekolah = :sekolah');
            $querybuilder->setParameter('tahun', $tahun);
            $querybuilder->setParameter('sekolah', $sekolah->getId());

            $results = $querybuilder->getQuery()->getResult();

            $students = array();
            foreach ($results as $result) {
                $students[] = array(
                        $result->getNomorIndukSistem(), $result->getNomorInduk(), $result->getNamaLengkap(),
                        $result->getJenisKelamin(), '', 1
                );
            }

            $fields = array(
                    'nomorIndukSistem', 'nomorInduk', 'namaLengkap', 'jenisKelamin', 'kodeJurusan', 'aktif',
                    'keterangan'
            );

            // ambil data kode jurusan
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Penjurusan', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.root ASC, t.lft', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $placements = $querybuilder->getQuery()->getResult();

            $response = $this
                    ->render("FastSisdikBundle:Siswa:$filename.twig",
                            array(
                                'fields' => $fields, 'students' => $students, 'placements' => $placements
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        $filename = "template_kelas_siswa_pertingkat.csv";

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $tahunAkademik = $form->get('tahunAkademik')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahunAkademik yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:SiswaKelas', 't')
                    ->leftJoin('t.siswa', 't2')->leftJoin('t.kelas', 't3')
                    ->where('t.tahunAkademik = :tahunAkademik')->andWhere('t2.sekolah = :sekolah')
                    ->orderBy('t3.kode, t2.nomorInduk');
            $querybuilder->setParameter('tahunAkademik', $tahunAkademik);
            $querybuilder->setParameter('sekolah', $sekolah->getId());

            $results = $querybuilder->getQuery()->getResult();

            $students = array();
            foreach ($results as $result) {
                $penjurusan = $result->getPenjurusan();
                $kodepenjurusan = (is_object($penjurusan) && $penjurusan instanceof Penjurusan) ? $penjurusan
                                ->getKode() : '';
                $students[] = array(
                        $result->getSiswa()->getNomorIndukSistem(), $result->getSiswa()->getNomorInduk(),
                        $result->getSiswa()->getNamaLengkap(), $result->getSiswa()->getJenisKelamin(),
                        $result->getKelas()->getKode(), $kodepenjurusan, $result->getAktif(),
                        $result->getKeterangan(),
                );
            }

            $fields = array(
                    'nomorIndukSistem', 'nomorInduk', 'namaLengkap', 'jenisKelamin', 'kodeKelas',
                    'kodeJurusan', 'aktif', 'keterangan'
            );

            // data kodeKelas
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                    ->leftJoin('t.tahunAkademik', 't2')->leftJoin('t.tingkat', 't3')
                    ->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'DESC')
                    ->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $classes = $querybuilder->getQuery()->getResult();

            // data kodeJurusan
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Penjurusan', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.root ASC, t.lft', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $placements = $querybuilder->getQuery()->getResult();

            $response = $this
                    ->render("FastSisdikBundle:Siswa:$filename.twig",
                            array(
                                    'fields' => $fields, 'students' => $students, 'classes' => $classes,
                                    'placements' => $placements
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaMergeType());

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->submit($this->getRequest());

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

                $this->get('session')->getFlashBag()
                        ->add('success',
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaExportType($this->container));

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $filename = "data_siswa_pertahun.csv";

            $tahun = $form->get('tahun')->getData()->getId();

            // ambil data seluruh siswa berdasarkan tahun masuk yang dipilih
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                    ->where('t.tahun = :tahun')->andWhere('t.sekolah = :sekolah');
            $querybuilder->setParameter('tahun', $tahun);
            $querybuilder->setParameter('sekolah', $sekolah->getId());

            $results = $querybuilder->getQuery()->getResult();

            $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
            $properties = $reflectionClass->getProperties();

            foreach ($properties as $property) {
                $fieldName = $property->getName();
                if (preg_match('/^id/', $fieldName) || $fieldName === 'file' || $fieldName === 'foto'
                        || $fieldName === 'nomorPendaftaran' || $fieldName === 'nomorUrutPersekolah'
                        || $fieldName === 'gelombang' || $fieldName === 'tahun' || $fieldName === 'sekolah')
                    continue;
                $fields[] = $fieldName;
            }

            $students = array();
            foreach ($results as $result) {
                reset($fields);
                unset($tmpdata);
                foreach ($fields as $field) {
                    if ($field == 'gelombang') {
                        $tmpdata[] = $result->{'get' . ucfirst($field)}()->getNama();
                    } else {
                        $tmpdata[] = $result->{'get' . ucfirst($field)}();
                    }
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

    private function importStudent($row, $headers, $sekolah, $tahun, $gelombang, $andFlush = false) {
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
                    $entity->setSekolah($sekolah);
                    $entity->setTahun($tahun);
                    $entity->setGelombang($gelombang);
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

    private function importStudentClass($row, $headers, $sekolah, $tahun, $kelas, $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

        // Create new siswakelas entity
        $siswakelas = new SiswaKelas();

        $key = array_search('NomorIndukSistem', $headers);
        if (is_int($key)) {
            $student = $em->getRepository('FastSisdikBundle:Siswa')
                    ->findOneBy(
                            array(
                                'nomorIndukSistem' => $row[$headers[$key]], 'sekolah' => $sekolah
                            ));

            if (!$student) {
                throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
            }

            $siswakelas->setSiswa($student);
        }

        $siswakelas->setTahun($tahun);
        $siswakelas->setKelas($kelas);

        $key = array_search('KodeJurusan', $headers);
        if (is_int($key)) {
            $placement = $em->getRepository('FastSisdikBundle:Penjurusan')
                    ->findOneBy(
                            array(
                                'kode' => $row[$headers[$key]], 'sekolah' => $sekolah
                            ));

            if (!$placement) {
                // allow null
                // throw $this->createNotFoundException('Entity Penjurusan tak ditemukan.');
            } else {
                $siswakelas->setPenjurusan($placement);
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
                                            'siswa' => $student->getId(), 'tahun' => $tahun->getId(),
                                            'aktif' => $aktif
                                    ));
                    if ($obj) {
                        $exception = $this->get('translator')->trans('exception.unique.studentclass.active');
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
        $menu['headings.academic']['links.data.student']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.useadmin.or.headmaster'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
