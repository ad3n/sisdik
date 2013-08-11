<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Fast\SisdikBundle\Form\PenempatanSiswaKelasType;
use Fast\SisdikBundle\Form\SiswaKelasTemplateMapType;
use Fast\SisdikBundle\Form\SiswaKelasTemplateInitType;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Util\EasyCSV\Reader;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa controller.
 *
 * @Route("/penempatan-siswa-kelas")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PenempatanSiswaKelasController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "siswa-kelas.";
    const DOCUMENTS_OUTPUTDIR = "uploads/siswa-kelas/";

    private $importStudentClassCount = 0;

    /**
     * Lists all Siswa entities.
     *
     * @Route("/", name="penempatan-siswa-kelas")
     * @Template()
     * @Method("GET")
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new PenempatanSiswaKelasType($this->container));

        $dlform_initialization = $this->createForm(new SiswaKelasTemplateInitType($this->container));
        $dlform_classmap = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        return array(
                'form' => $form->createView(),
                'dlform_initialization' => $dlform_initialization->createView(),
                'dlform_classmap' => $dlform_classmap->createView(),
        );
    }

    /**
     * Penempatan siswa ke kelas
     *
     * @Route("/menempatkan", name="penempatan-siswa-kelas_menempatkan")
     * @Method("POST")
     * @Template("FastSisdikBundle:PenempatanSiswaKelas:index.html.twig")
     */
    public function menempatkanSiswaKelasAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new PenempatanSiswaKelasType($this->container));
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

            return $this->redirect($this->generateUrl('penempatan-siswa-kelas'));
        }

        $dlform_initialization = $this->createForm(new SiswaKelasTemplateInitType($this->container));
        $dlform_classmap = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        return array(
                'form' => $form->createView(),
                'dlform_initialization' => $dlform_initialization->createView(),
                'dlform_classmap' => $dlform_classmap->createView(),
        );
    }

    /**
     * Unduh file template untuk inisialisasi penempatan siswa di kelas
     * Menggunakan tahun masuk
     *
     * @Route("/dl-templateinit", name="penempatan-siswa-kelas_templateinit")
     * @Method("POST")
     */
    public function downloadSiswaKelasTemplateInitAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateInitType($this->container));

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $querybuilder = $em->createQueryBuilder()->select('siswa')
                    ->from('FastSisdikBundle:Siswa', 'siswa')->leftJoin('siswa.siswaKelas', 'siswakelas')
                    ->where('siswa.tahun = :tahun')->andWhere('siswa.sekolah = :sekolah')
                    ->andWhere('siswa.calonSiswa = :calon')->andWhere('siswakelas.id IS NULL')
                    ->setParameter('tahun', $formdata['tahun']->getId())
                    ->setParameter('sekolah', $sekolah->getId())->setParameter('calon', false);
            $entities = $querybuilder->getQuery()->getResult();

            $qbjurusan = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Penjurusan', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.root ASC, t.lft', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $penjurusan = $qbjurusan->getQuery()->getResult();

            $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
            $outputdir = self::DOCUMENTS_OUTPUTDIR;

            $filenameoutput = self::OUTPUTFILE . $formdata['tahun']->getTahun() . ".sisdik";

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput . $extensionsource;
            $filetarget = $filenameoutput . $extensiontarget;

            $fs = new Filesystem();
            if (!$fs->exists($outputdir . $sekolah->getId())) {
                $fs->mkdir($outputdir . $sekolah->getId());
            }

            $documentsource = $outputdir . $sekolah->getId() . '/' . $filesource;
            $documenttarget = $outputdir . $sekolah->getId() . '/' . $filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === TRUE) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive
                            ->addFromString('styles.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:styles.xml.twig"));
                    $ziparchive
                            ->addFromString('settings.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:settings.xml.twig"));
                    $ziparchive
                            ->addFromString('content.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:siswakelas-awal.xml.twig",
                                                    array(
                                                        'entities' => $entities, 'penjurusan' => $penjurusan
                                                    )));
                    if ($ziparchive->close() === TRUE) {
                        $return = array(
                                "redirectUrl" => $this
                                        ->generateUrl("penempatan-siswa-kelas_unduh",
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
    }

    /**
     * Unduh file untuk kenaikan kelas
     * Menggunakan tahun akademik dan tingkat kelas
     *
     * @Route("/dl-templatemap", name="penempatan-siswa-kelas_templatemap")
     * @Method("POST")
     */
    public function downloadSiswaKelasTemplateMapAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaKelasTemplateMapType($this->container));

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();

            $em = $this->getDoctrine()->getManager();

            $qbkelas = $em->createQueryBuilder()->select('kelas')->from('FastSisdikBundle:Kelas', 'kelas')
                    ->where('kelas.sekolah = :sekolah')->andWhere('kelas.tahunAkademik = :tahunakademik')
                    ->andWhere('kelas.tingkat = :tingkat')->orderBy('kelas.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId())
                    ->setParameter('tahunakademik', $formdata['tahunAkademik']->getId())
                    ->setParameter('tingkat', $formdata['tingkat']->getId());
            $kelas = $qbkelas->getQuery()->getResult();

            foreach ($kelas as $data) {
                $querybuilder = $em->createQueryBuilder()->select('siswakelas')
                        ->from('FastSisdikBundle:SiswaKelas', 'siswakelas')
                        ->leftJoin('siswakelas.siswa', 'siswa')->leftJoin('siswakelas.kelas', 'kelas')
                        ->where('siswakelas.tahunAkademik = :tahunAkademik')
                        ->andWhere('siswa.sekolah = :sekolah')->andWhere('siswa.calonSiswa = :calon')
                        ->andWhere('siswakelas.kelas = :kelas')->orderBy('kelas.urutan', 'ASC')
                        ->addOrderBy('siswa.nomorInduk', 'ASC')
                        ->setParameter('tahunAkademik', $formdata['tahunAkademik']->getId())
                        ->setParameter('sekolah', $sekolah->getId())->setParameter('calon', false)
                        ->setParameter('kelas', $data->getId());
                $entities[] = $querybuilder->getQuery()->getResult();
            }

            $qbjurusan = $em->createQueryBuilder()->select('penjurusan')
                    ->from('FastSisdikBundle:Penjurusan', 'penjurusan')
                    ->where('penjurusan.sekolah = :sekolah')
                    ->orderBy('penjurusan.root ASC, penjurusan.lft', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $penjurusan = $qbjurusan->getQuery()->getResult();

            $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
            $outputdir = self::DOCUMENTS_OUTPUTDIR;

            $filenameoutput = self::OUTPUTFILE . $formdata['tahunAkademik']->getNama() . '.'
                    . $formdata['tingkat']->getKode() . ".sisdik";

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput . $extensionsource;
            $filetarget = $filenameoutput . $extensiontarget;

            $fs = new Filesystem();
            if (!$fs->exists($outputdir . $sekolah->getId())) {
                $fs->mkdir($outputdir . $sekolah->getId());
            }

            $documentsource = $outputdir . $sekolah->getId() . '/' . $filesource;
            $documenttarget = $outputdir . $sekolah->getId() . '/' . $filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === TRUE) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive
                            ->addFromString('styles.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:styles.xml.twig"));
                    $ziparchive
                            ->addFromString('settings.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:settings.multipage.xml.twig",
                                                    array(
                                                        'kelas' => $kelas
                                                    )));
                    $ziparchive
                            ->addFromString('content.xml',
                                    $this
                                            ->renderView(
                                                    "FastSisdikBundle:PenempatanSiswaKelas:siswakelas-kenaikan.xml.twig",
                                                    array(
                                                            'kelas' => $kelas, 'entities' => $entities,
                                                            'penjurusan' => $penjurusan
                                                    )));
                    if ($ziparchive->close() === TRUE) {
                        $return = array(
                                "redirectUrl" => $this
                                        ->generateUrl("penempatan-siswa-kelas_unduh",
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
    }

    /**
     * download the generated file
     *
     * @Route("/unduh/{filename}/{type}", name="penempatan-siswa-kelas_unduh")
     * @Method("GET")
     */
    public function downloadFileAction($filename, $type = 'ods') {
        $sekolah = $this->isRegisteredToSchool();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Penempatan Siswa Kelas');

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

    /**
     *
     * @param  string                                 $row
     * @param  array                                  $headers
     * @param  Fast\SisdikBundle\Entity\Sekolah       $sekolah
     * @param  Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @param  Fast\SisdikBundle\Entity\Kelas         $kelas
     * @param  boolean                                $andFlush
     * @throws \Exception
     */
    private function importStudentClass($row, $headers, $sekolah, $tahunAkademik, $kelas, $andFlush = false) {
        $em = $this->getDoctrine()->getManager();

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

        $siswakelas->setTahunAkademik($tahunAkademik);
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
                // siswa hanya boleh berstatus aktif di satu kelas dalam satu tahun akademik aktif
                $aktif = $row[$headers[$key]];
                if ($aktif == 1) {
                    $obj = $em->getRepository('FastSisdikBundle:SiswaKelas')
                            ->findOneBy(
                                    array(
                                            'siswa' => $student->getId(),
                                            'tahunAkademik' => $tahunAkademik->getId(), 'aktif' => $aktif
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

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.penempatan.siswa.kelas']->setCurrent(true);
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
