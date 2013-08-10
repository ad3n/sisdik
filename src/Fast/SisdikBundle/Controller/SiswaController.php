<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
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
use Fast\SisdikBundle\Form\SiswaType;
use Fast\SisdikBundle\Form\SiswaSearchType;
use Fast\SisdikBundle\Form\SiswaImportType;
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
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "datasiswa.";
    const DOCUMENTS_OUTPUTDIR = "uploads/data-siswa/";

    private $importStudentCount = 0;
    private $mergeStudentCount = 0;

    /**
     * Lists all Siswa entities.
     *
     * @Route("/", name="siswa")
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
     * @Route("/{id}/show", name="siswa_show")
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
     * @Route("/new", name="siswa_new")
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
     * @Route("/create", name="siswa_create")
     * @Method("POST")
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
                                    ->generateUrl('siswa_show',
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
     * @Route("/{id}/edit", name="siswa_edit")
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
     * @Route("/{id}/update", name="siswa_update")
     * @Method("POST")
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
                                    ->generateUrl('siswa_edit',
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
     * @Route("/{id}/deleteconfirm", name="siswa_deleteconfirm")
     * @Method("POST")
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
     * @Route("/{id}/delete", name="siswa_delete")
     * @Method("POST")
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

        return $this->redirect($this->generateUrl('siswa'));
    }

    /**
     * Displays a form to import Siswa entities.
     *
     * @Route("/import/student", name="siswa_import_student")
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

                return $this->redirect($this->generateUrl('siswa'));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * Download a csv format file template to import Siswa entities
     *
     * @Route("/download/studenttemplate", name="siswa_student_template")
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
     * Displays a form to import and merge Siswa entities.
     *
     * @Route("/merge/student", name="siswa_merge_student")
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

                return $this->redirect($this->generateUrl('siswa'));
            }
        }

        $dlform = $this->createForm(new SiswaExportType($this->container));

        return array(
            'form' => $form->createView(), 'dlform' => $dlform->createView()
        );
    }

    /**
     * Ekspor data siswa per tahun
     *
     * @Route("/ekspor", name="siswa_export")
     * @Method("POST")
     */
    public function exportAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaExportType($this->container));

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $querybuilder = $em->createQueryBuilder()->select('siswa')
                    ->from('FastSisdikBundle:Siswa', 'siswa')->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.sekolah = :sekolah')->andWhere('siswa.calonSiswa = :calon')
                    ->setParameter('tahun', $formdata['tahun']->getId())
                    ->setParameter('sekolah', $sekolah->getId())->setParameter('calon', false);
            $entities = $querybuilder->getQuery()->getResult();

            $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
            $outputdir = self::DOCUMENTS_OUTPUTDIR;

            $filenameoutput = self::OUTPUTFILE . $formdata['tahun']->getTahun() . ".sisdik";

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput . $extensionsource;
            $filetarget = $filenameoutput . $extensiontarget;

            $fs = new Filesystem();
            if (!$fs->exists($outputdir . $sekolah->getId() . '/' . $formdata['tahun']->getTahun())) {
                $fs->mkdir($outputdir . $sekolah->getId() . '/' . $formdata['tahun']->getTahun());
            }

            $documentsource = $outputdir . $sekolah->getId() . '/' . $formdata['tahun']->getTahun() . '/'
                    . $filesource;
            $documenttarget = $outputdir . $sekolah->getId() . '/' . $formdata['tahun']->getTahun() . '/'
                    . $filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === TRUE) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive
                            ->addFromString('content.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:Siswa:datasiswa-pertahun.xml.twig",
                                                    array(
                                                            'entities' => $entities,
                                                            'jumlahSiswa' => count($entities)
                                                    )));
                    if ($ziparchive->close() === TRUE) {
                        $return = array(
                                "redirectUrl" => $this
                                        ->generateUrl("siswa_downloadfile",
                                                array(
                                                        'tahun' => $formdata['tahun']->getTahun(),
                                                        'filename' => $filetarget
                                                )), "tahun" => $formdata['tahun']->getTahun(),
                                "filename" => $filetarget,
                        );

                        $return = json_encode($return);

                        return new Response($return, 200,
                                array(
                                    'Content-Type' => 'application/json'
                                ));
                    }
                }
            }
        }
    }

    /**
     * download the generated file
     *
     * @Route("/download/{tahun}/{filename}/{type}", name="siswa_downloadfile")
     * @Method("GET")
     */
    public function downloadFileAction($tahun, $filename, $type = 'ods') {
        $sekolah = $this->isRegisteredToSchool();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $tahun . '/' . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Data Siswa');

        if ($type == 'ods') {
            $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        } elseif ($type == 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf');
        }

        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', filesize($documenttarget));

        return $response;
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
        $menu['headings.academic']['links.siswa']->setCurrent(true);
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
