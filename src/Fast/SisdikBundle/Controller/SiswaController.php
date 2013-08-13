<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\SekolahAsal;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormError;
use Fast\SisdikBundle\Util\SpreadsheetReader\SpreadsheetReader;
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
    const DATASISWA_OUTPUTFILE = "datasiswa.";
    const TEMPLATE_OUTPUTFILE = "template-file.";
    const DOCUMENTS_OUTPUTDIR = "uploads/data-siswa/";

    private $imporSiswaJumlah = 0;
    private $mergeStudentCount = 0;
    private $nomorUrutPersekolah = 0;

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
     * @Route("/impor-baru", name="siswa_imporbaru")
     * @Template("FastSisdikBundle:Siswa:impor-baru.html.twig")
     * @Method("GET")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function imporBaruAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaImportType($this->container));

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * Mengimpor data siswa baru
     *
     * @Route("/mengimpor-baru", name="siswa_mengimporbaru")
     * @Template("FastSisdikBundle:Siswa:impor-baru.html.twig")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function mengimporBaruAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaImportType($this->container));

        $form->submit($this->getRequest());

        $filedata = $form['file']->getData();
        if ($filedata instanceof UploadedFile) {

            $reader = new SpreadsheetReader($filedata->getPathname(), false, $filedata->getClientMimeType());
            $sheets = $reader->Sheets();
            if (count($sheets) > 1) {
                $message = $this->get('translator')->trans('alert.hanya.boleh.satu.lembar.kerja');
                $form->get('file')->addError(new FormError($message));
            }
            unset($reader);

        }

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $file = $form['file']->getData();
            $tahun = $form['tahun']->getData();

            $targetfilename = $file->getClientOriginalName();
            if ($file->move(self::DOCUMENTS_OUTPUTDIR, $targetfilename)) {

                $reader = new SpreadsheetReader(self::DOCUMENTS_OUTPUTDIR . $targetfilename);

                $fieldnames = array();
                $content = array();
                foreach ($reader as $row) {
                    $cellContent = array();
                    foreach ($row as $cell) {
                        if (array_key_exists('table:style-name', $cell['attributes'])
                                && $cell['attributes']['table:style-name'] == 'nama-kolom') {
                            $fieldnames[] = $cell['data'];
                        } elseif (array_key_exists('table:style-name', $cell['attributes'])
                                && $cell['attributes']['table:style-name'] == 'nama-kolom-deskriptif') {
                            // baris yang tak perlu dibaca
                        } else {
                            $cellContent[] = $cell['data'];
                        }
                    }
                    if (count($cellContent) > 0) {
                        $content[] = $cellContent;
                    }
                }

                array_walk($fieldnames,
                        array(
                            &$this, "formatNamaField"
                        ));

                foreach ($content as $value) {
                    $this->imporSiswaBaru($value, $fieldnames, $sekolah, $tahun);
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
                                                        '%count%' => $this->imporSiswaJumlah,
                                                        '%year%' => $tahun->getTahun(),
                                                )));

                return $this->redirect($this->generateUrl('siswa_imporbaru'));

            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    private function formatNamaField(&$item, $key) {
        preg_match("/(\d+:)(.+)/", $item, $matches);
        $item = $matches[2];
    }

    /**
     * Displays a form to import and merge Siswa entities.
     *
     * @Route("/impor-gabung", name="siswa_imporgabung")
     * @Template("FastSisdikBundle:Siswa:impor-gabung.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function imporGabungAction() {
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
     * Unduh file template untuk mengimpor-baru data siswa
     *
     * @Route("/file-template", name="siswa_file_template")
     * @Method("GET")
     */
    public function fileTemplateAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::TEMPLATE_OUTPUTFILE . "sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir . $sekolah->getId())) {
            $fs->mkdir($outputdir . $sekolah->getId());
        }

        $documentsource = $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR . $filesource;
        $documenttarget = $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR . $filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive
                        ->addFromString('styles.xml',
                                $this->renderView("FastSisdikBundle:Siswa:styles.xml.twig"));
                $ziparchive
                        ->addFromString('settings.xml',
                                $this->renderView("FastSisdikBundle:Siswa:settings.xml.twig"));
                $ziparchive
                        ->addFromString('content.xml',
                                $this->renderView("FastSisdikBundle:Siswa:template-file.xml.twig"));
                if ($ziparchive->close() === TRUE) {
                    $return = array(
                            "redirectUrl" => $this
                                    ->generateUrl("siswa_downloadfile",
                                            array(
                                                'filename' => $filetarget
                                            )), "filename" => $filetarget,
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

            $filenameoutput = self::DATASISWA_OUTPUTFILE . $formdata['tahun']->getTahun() . ".sisdik";

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput . $extensionsource;
            $filetarget = $filenameoutput . $extensiontarget;

            $fs = new Filesystem();
            if (!$fs
                    ->exists(
                            $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR
                                    . $formdata['tahun']->getTahun())) {
                $fs
                        ->mkdir(
                                $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR
                                        . $formdata['tahun']->getTahun());
            }

            $documentsource = $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR
                    . $formdata['tahun']->getTahun() . DIRECTORY_SEPARATOR . $filesource;
            $documenttarget = $outputdir . $sekolah->getId() . DIRECTORY_SEPARATOR
                    . $formdata['tahun']->getTahun() . DIRECTORY_SEPARATOR . $filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === TRUE) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive
                            ->addFromString('styles.xml',
                                    $this->renderView("FastSisdikBundle:Siswa:styles.xml.twig"));
                    $ziparchive
                            ->addFromString('settings.xml',
                                    $this->renderView("FastSisdikBundle:Siswa:settings.xml.twig"));
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
                                                        'filename' => $filetarget,
                                                        'tahun' => $formdata['tahun']->getTahun(),
                                                )), "filename" => $filetarget,
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
     * @Route("/download/{filename}/{type}/{tahun}", name="siswa_downloadfile")
     * @Method("GET")
     */
    public function downloadFileAction($filename, $type = 'ods', $tahun = '') {
        $sekolah = $this->isRegisteredToSchool();

        $filetarget = $filename;
        $documenttarget = $tahun != '' ? self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . DIRECTORY_SEPARATOR
                        . $tahun . DIRECTORY_SEPARATOR . $filetarget
                : self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . DIRECTORY_SEPARATOR . $filetarget;

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

    /**
     * mengimpor data siswa baru
     *
     * @param array                             $row
     * @param array                             $fieldnames
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @param \Fast\SisdikBundle\Entity\Tahun   $tahun
     * @param boolean                           $andFlush
     */
    private function imporSiswaBaru($content, $fieldnames, $sekolah, $tahun, $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

        $atleastone = false;
        foreach ($content as $key => $val) {
            if ($val != "") {
                $atleastone = true;
            }
        }
        if (!$atleastone)
            return;

        $entity = new Siswa();

        $reflectionClass = new \ReflectionClass('Fast\SisdikBundle\Entity\Siswa');
        $entityFields = array();
        foreach ($reflectionClass->getProperties() as $property) {
            $entityFields[] = $property->getName();
        }

        $matchcountOrtuwali = 0;
        $orangtuaWali = new ArrayCollection();
        $ortu = new OrangtuaWali();
        $sekolahAsal = null;

        foreach ($fieldnames as $keyfield => $valuefield) {

            // periksa jika field berisi titik
            if (preg_match("/(.+)\.(.+)/", $valuefield, $matches)) {
                $valuefield = $matches[1];
                $childfield = $matches[2];
            }

            $key = array_search($valuefield, $entityFields);
            if (is_int($key)) {
                if (array_key_exists($keyfield, $content)) {

                    $value = $content[$keyfield];
                    if ($value == "0" || $value == "")
                        $value = null;

                    if ($valuefield == 'orangtuaWali') {
                        $ortu->setAktif(true);
                        $ortu->{'set' . ucfirst($childfield)}(trim($value));
                    } elseif ($valuefield == 'sekolahAsal') {
                        $querySekolahAsal = $em->createQueryBuilder()->select('sekolahasal')
                                ->from('FastSisdikBundle:SekolahAsal', 'sekolahasal')
                                ->where('sekolahasal.nama LIKE :nama')->setParameter('nama', "%$value%");
                        $resultSekolahAsal = $querySekolahAsal->getQuery()->getResult();
                        if (count($resultSekolahAsal) >= 1) {
                            $sekolahAsal = $resultSekolahAsal[0];
                        } else {
                            $sekolahAsal = new SekolahAsal();
                            $sekolahAsal->{'set' . ucfirst($childfield)}(trim($value));
                        }

                    } elseif ($valuefield == 'tanggalLahir') {
                        if ($value) {
                            $entity->{'set' . ucfirst($valuefield)}(new \DateTime($value));
                        }
                    } else {
                        $entity->{'set' . ucfirst($valuefield)}(trim($value));
                    }
                }
            }
        }

        if ($this->nomorUrutPersekolah == 0) {
            $qbe = $em->createQueryBuilder();
            $querynomor = $em->createQueryBuilder()->select($qbe->expr()->max('siswa.nomorUrutPersekolah'))
                    ->from('FastSisdikBundle:Siswa', 'siswa')->where('siswa.sekolah = :sekolah')
                    ->setParameter('sekolah', $sekolah->getId());
            $nomorUrutPersekolah = $querynomor->getQuery()->getSingleScalarResult();
            $nomorUrutPersekolah = $nomorUrutPersekolah === null ? 100000 : $nomorUrutPersekolah;
            $nomorUrutPersekolah++;
            $this->nomorUrutPersekolah = $nomorUrutPersekolah;
        } else {
            $this->nomorUrutPersekolah++;
        }

        $entity->setNomorUrutPersekolah($this->nomorUrutPersekolah);
        $entity->setNomorIndukSistem($this->nomorUrutPersekolah . $sekolah->getNomorUrut());
        $entity->setCalonSiswa(false);
        $entity->setSekolah($sekolah);
        $entity->setTahun($tahun);
        $entity->setGelombang(null);

        $orangtuaWali->add($ortu);
        $entity->setOrangtuaWali($orangtuaWali);

        $entity->setSekolahAsal($sekolahAsal);
        $entity->setDibuatOleh($this->getUser());

        $em->persist($entity);

        $this->imporSiswaJumlah++;

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
