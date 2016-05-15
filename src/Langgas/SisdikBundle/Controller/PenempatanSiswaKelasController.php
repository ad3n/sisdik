<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Util\EasyCSV\Reader;
use Langgas\SisdikBundle\Util\SpreadsheetReader\SpreadsheetReader;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/penempatan-siswa-kelas")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PenempatanSiswaKelasController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "siswa-kelas.";
    const DOCUMENTS_OUTPUTDIR = "uploads/siswa-kelas/";
    const PENEMPATAN_FILE = "penempatan_siswa_kelas_file";

    private $siswaDitempatkanJumlah = 0;

    /**
     * @Route("/", name="penempatan-siswa-kelas")
     * @Template()
     * @Method("GET")
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('sisdik_penempatansiswakelas');

        $dlform_initialization = $this->createForm('sisdik_siswakelastemplateinit');
        $dlform_classmap = $this->createForm('sisdik_siswakelastemplatemap');

        return [
            'form' => $form->createView(),
            'dlform_initialization' => $dlform_initialization->createView(),
            'dlform_classmap' => $dlform_classmap->createView(),
        ];
    }

    /**
     * Menempatkan siswa-siswa ke kelas
     *
     * @Route("/menempatkan", name="penempatan-siswa-kelas_menempatkan")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenempatanSiswaKelas:index.html.twig")
     */
    public function menempatkanAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_penempatansiswakelas');
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $file = $form['file']->getData();
            $tahunAkademik = $form['tahunAkademik']->getData();
            $kelas = $form['kelas']->getData();

            $targetfilename = sha1(uniqid(mt_rand(), true)).'_'.$file->getClientOriginalName();
            if ($file->move(self::DOCUMENTS_OUTPUTDIR, $targetfilename)) {
                $reader = new SpreadsheetReader(self::DOCUMENTS_OUTPUTDIR.$targetfilename);
                $sheets = $reader->Sheets();
                if (count($sheets) > 1) {
                    // interface untuk menanyakan sheet mana masuk ke kelas mana
                    $this->get('session')->set(self::PENEMPATAN_FILE, $targetfilename);

                    return $this->redirect($this->generateUrl('penempatan-siswa-kelas_tempatkan-kelompok'));
                }

                $fieldnames = [];
                $content = [];
                foreach ($reader as $row) {
                    $cellContent = [];
                    foreach ($row as $cell) {
                        $fieldCocok = [];
                        if (preg_match("/^(\d+:)(.*)/", $cell['data'], $fieldCocok)) {
                            $fieldnames[] = $fieldCocok[2];
                        } elseif (preg_match("/^\[.*\]$/", $cell['data'])) {
                            // baris header perlu diabaikan
                        } else {
                            $cellContent[] = $cell['data'];
                        }
                    }
                    if (count($cellContent) > 0) {
                        $content[] = $cellContent;
                    }
                }

                foreach ($content as $value) {
                    $this->menempatkanSiswa($value, $fieldnames, $sekolah, $tahunAkademik, $kelas);
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentclass.unique');
                    throw new DBALException($message);
                } catch (\Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.studentclass.imported', [
                        '%count%' => $this->siswaDitempatkanJumlah,
                        '%year%' => $tahunAkademik->getNama(),
                        '%class%' => $kelas->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('penempatan-siswa-kelas'));
            }
        }

        $dlform_initialization = $this->createForm('sisdik_siswakelastemplateinit');
        $dlform_classmap = $this->createForm('sisdik_siswakelastemplatemap');

        return [
            'form' => $form->createView(),
            'dlform_initialization' => $dlform_initialization->createView(),
            'dlform_classmap' => $dlform_classmap->createView(),
        ];
    }

    /**
     * Form untuk menempatkan lebih dari satu kelompok siswa ke kelas-kelas
     *
     * @Route("/tempatkan-kelompok", name="penempatan-siswa-kelas_tempatkan-kelompok")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:PenempatanSiswaKelas:tempatkan-kelompok.html.twig")
     */
    public function tempatkanKelompokAction()
    {
        $this->setCurrentMenu();

        $filename = $this->get('session')->get(self::PENEMPATAN_FILE);

        $reader = new SpreadsheetReader(self::DOCUMENTS_OUTPUTDIR.$filename);
        $sheets = $reader->Sheets();

        $sheetCollection = new ArrayCollection();
        foreach ($sheets as $index => $name) {
            $sheetCollection->add([
                'index' => $index,
                'name' => $name,
            ]);
        }

        $form = $this->createForm('collection', $sheetCollection, [
            'type' => 'sisdik_penempatansiswakelaskelompok',
            'required' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'options' => [
                'label_render' => false,
            ],
            'label_render' => false,
        ]);

        return [
            'form' => $form->createView(),
            'sheetCollection' => $sheetCollection,
        ];
    }

    /**
     * Menempatkan kelompok siswa ke kelas-kelas
     *
     * @Route("/menempatkan-kelompok", name="penempatan-siswa-kelas_menempatkan-kelompok")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenempatanSiswaKelas:tempatkan-kelompok.html.twig")
     */
    public function menempatkanKelompokAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $filename = $this->get('session')->get(self::PENEMPATAN_FILE);

        $reader = new SpreadsheetReader(self::DOCUMENTS_OUTPUTDIR.$filename);
        $sheets = $reader->Sheets();

        $sheetCollection = new ArrayCollection();

        $form = $this->createForm('collection', $sheetCollection, [
            'type' => 'sisdik_penempatansiswakelaskelompok',
            'required' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'options' => [
                'label_render' => false,
            ],
            'label_render' => false,
        ]);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();
            foreach ($formdata as $data) {
                $reader->ChangeSheet($data['index']);

                $fieldnames = [];
                $content = [];
                foreach ($reader as $row) {
                    $cellContent = [];
                    foreach ($row as $cell) {
                        $fieldCocok = [];
                        if (preg_match("/^(\d+:)(.*)/", $cell['data'], $fieldCocok)) {
                            $fieldnames[] = $fieldCocok[2];
                        } elseif (preg_match("/^\[.*\]$/", $cell['data'])) {
                            // baris header perlu diabaikan
                        } else {
                            $cellContent[] = $cell['data'];
                        }
                    }
                    if (count($cellContent) > 0) {
                        $content[] = $cellContent;
                    }
                }

                foreach ($content as $value) {
                    $this->menempatkanSiswa($value, $fieldnames, $sekolah, $data['tahunAkademik'], $data['kelas']);
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentclass.unique');
                    throw new DBALException($message);
                } catch (\Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }
            }

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.studentclass.imported.group', [
                    '%count%' => $this->siswaDitempatkanJumlah,
                ]))
            ;

            return $this->redirect($this->generateUrl('penempatan-siswa-kelas'));
        }

        return [
            'form' => $form->createView(),
            'sheetCollection' => $sheetCollection,
        ];
    }

    /**
     * Unduh file template untuk inisialisasi penempatan siswa di kelas menggunakan tahun masuk
     *
     * @Route("/dl-templateinit", name="penempatan-siswa-kelas_templateinit")
     * @Method("POST")
     */
    public function downloadTemplateInitAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_siswakelastemplateinit');

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $querybuilder = $em->createQueryBuilder()
                ->select('siswa')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.siswaKelas', 'siswakelas')
                ->where('siswa.tahun = :tahun')
                ->andWhere('siswa.sekolah = :sekolah')
                ->andWhere('siswa.calonSiswa = :calon')
                ->andWhere('siswakelas.id IS NULL')
                ->setParameter('tahun', $formdata['tahun'])
                ->setParameter('sekolah', $sekolah)
                ->setParameter('calon', false)
            ;
            $entities = $querybuilder->getQuery()->getResult();

            $qbjurusan = $em->createQueryBuilder()
                ->select('penjurusan')
                ->from('LanggasSisdikBundle:Penjurusan', 'penjurusan')
                ->where('penjurusan.sekolah = :sekolah')
                ->orderBy('penjurusan.root', 'ASC')
                ->addOrderBy('penjurusan.lft', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
            $penjurusan = $qbjurusan->getQuery()->getResult();

            $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
            $outputdir = self::DOCUMENTS_OUTPUTDIR;

            $patterns = ['/\s+/', '/\//'];
            $replacements = ['', '_'];
            $filenameoutput = self::OUTPUTFILE.preg_replace($patterns, $replacements, $formdata['tahun']->getTahun()).".sisdik";

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput.$extensionsource;
            $filetarget = $filenameoutput.$extensiontarget;

            $fs = new Filesystem();
            if (!$fs->exists($outputdir.$sekolah->getId())) {
                $fs->mkdir($outputdir.$sekolah->getId());
            }

            $documentsource = $outputdir.$sekolah->getId().'/'.$filesource;
            $documenttarget = $outputdir.$sekolah->getId().'/'.$filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === true) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:styles.xml.twig"));
                    $ziparchive->addFromString('settings.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:settings.xml.twig"));
                    $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:siswakelas-awal.xml.twig", [
                        'entities' => $entities, 'penjurusan' => $penjurusan,
                    ]));

                    if ($ziparchive->close() === true) {
                        $return = [
                            "redirectUrl" => $this->generateUrl("penempatan-siswa-kelas_unduh", [
                                'filename' => $filetarget,
                            ]),
                            "filename" => $filetarget,
                        ];

                        $return = json_encode($return);

                        return new Response($return, 200, [
                            'Content-Type' => 'application/json',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Unduh file untuk kenaikan kelas menggunakan tahun akademik dan tingkat kelas
     *
     * @Route("/dl-templatemap", name="penempatan-siswa-kelas_templatemap")
     * @Method("POST")
     */
    public function downloadTemplateMapAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_siswakelastemplatemap');

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $formdata = $form->getData();

            $em = $this->getDoctrine()->getManager();

            $qbkelas = $em->createQueryBuilder()
                ->select('kelas')
                ->from('LanggasSisdikBundle:Kelas', 'kelas')
                ->where('kelas.sekolah = :sekolah')
                ->andWhere('kelas.tahunAkademik = :tahunakademik')
                ->andWhere('kelas.tingkat = :tingkat')
                ->orderBy('kelas.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah)
                ->setParameter('tahunakademik', $formdata['tahunAkademik'])
                ->setParameter('tingkat', $formdata['tingkat'])
            ;
            $kelas = $qbkelas->getQuery()->getResult();

            $entities = [];
            foreach ($kelas as $data) {
                $querybuilder = $em->createQueryBuilder()
                    ->select('siswakelas')
                    ->from('LanggasSisdikBundle:SiswaKelas', 'siswakelas')
                    ->leftJoin('siswakelas.siswa', 'siswa')
                    ->leftJoin('siswakelas.kelas', 'kelas')
                    ->where('siswakelas.tahunAkademik = :tahunAkademik')
                    ->andWhere('siswa.sekolah = :sekolah')
                    ->andWhere('siswa.calonSiswa = :calon')
                    ->andWhere('siswakelas.kelas = :kelas')
                    ->orderBy('kelas.urutan', 'ASC')
                    ->addOrderBy('siswa.nomorInduk', 'ASC')
                    ->setParameter('tahunAkademik', $formdata['tahunAkademik'])
                    ->setParameter('sekolah', $sekolah)
                    ->setParameter('calon', false)
                    ->setParameter('kelas', $data)
                ;
                $entities[] = $querybuilder->getQuery()->getResult();
            }

            $qbjurusan = $em->createQueryBuilder()
                ->select('penjurusan')
                ->from('LanggasSisdikBundle:Penjurusan', 'penjurusan')
                ->where('penjurusan.sekolah = :sekolah')
                ->orderBy('penjurusan.root', 'ASC')
                ->addOrderBy('penjurusan.lft', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
            $penjurusan = $qbjurusan->getQuery()->getResult();

            $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_BASEDIR.self::BASEFILE;
            $outputdir = self::DOCUMENTS_OUTPUTDIR;

            $patterns = ['/\s+/', '/\//'];
            $replacements = ['', '_'];
            $filenameoutput = self::OUTPUTFILE
                .preg_replace($patterns, $replacements, $formdata['tahunAkademik']->getNama())
                .'.'
                .preg_replace($patterns, $replacements, $formdata['tingkat']->getKode())
                .".sisdik"
            ;

            $outputfiletype = "ods";
            $extensiontarget = $extensionsource = ".$outputfiletype";
            $filesource = $filenameoutput.$extensionsource;
            $filetarget = $filenameoutput.$extensiontarget;

            $fs = new Filesystem();
            if (!$fs->exists($outputdir.$sekolah->getId())) {
                $fs->mkdir($outputdir.$sekolah->getId());
            }

            $documentsource = $outputdir.$sekolah->getId().'/'.$filesource;
            $documenttarget = $outputdir.$sekolah->getId().'/'.$filetarget;

            if ($outputfiletype == 'ods') {
                if (copy($documentbase, $documenttarget) === true) {
                    $ziparchive = new \ZipArchive();
                    $ziparchive->open($documenttarget);
                    $ziparchive->addFromString('styles.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:styles.xml.twig"));
                    $ziparchive->addFromString('settings.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:settings.multipage.xml.twig", [
                        'kelas' => $kelas,
                    ]));
                    $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:PenempatanSiswaKelas:siswakelas-kenaikan.xml.twig", [
                        'kelas' => $kelas,
                        'entities' => $entities,
                        'penjurusan' => $penjurusan,
                    ]));
                    if ($ziparchive->close() === true) {
                        $return = [
                            "redirectUrl" => $this->generateUrl("penempatan-siswa-kelas_unduh", [
                                'filename' => $filetarget,
                            ]),
                            "filename" => $filetarget,
                        ];

                        $return = json_encode($return);

                        return new Response($return, 200, [
                            'Content-Type' => 'application/json',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Unduh berkas yang telah dibuat
     *
     * @Route("/unduh/{filename}/{type}", name="penempatan-siswa-kelas_unduh")
     * @Method("GET")
     */
    public function downloadFileAction($filename, $type = 'ods')
    {
        $sekolah = $this->getSekolah();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR.$sekolah->getId().'/'.$filetarget;

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
     * @param  array         $content
     * @param  array         $fieldnames
     * @param  Sekolah       $sekolah
     * @param  TahunAkademik $tahunAkademik
     * @param  Kelas         $kelas
     * @param  boolean       $andFlush
     * @throws \Exception
     */
    private function menempatkanSiswa($content, $fieldnames, $sekolah, $tahunAkademik, $kelas, $andFlush = false)
    {
        $em = $this->getDoctrine()->getManager();

        $keyNomorIndukSistem = array_search('nomorIndukSistem', $fieldnames);
        if (is_int($keyNomorIndukSistem)) {
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                ->findOneBy([
                    'nomorIndukSistem' => $content[$keyNomorIndukSistem],
                    'sekolah' => $sekolah,
                ])
            ;
            if (!$siswa && !($siswa instanceof Siswa)) {
                return;
            }

            $siswaKelas = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
                ->findOneBy([
                    'siswa' => $siswa,
                    'tahunAkademik' => $tahunAkademik,
                    'kelas' => $kelas,
                ])
            ;

            if (!($siswaKelas instanceof SiswaKelas)) {
                $siswaKelas = new SiswaKelas();

                $siswaKelas->setSiswa($siswa);
                $siswaKelas->setTahunAkademik($tahunAkademik);
                $siswaKelas->setKelas($kelas);
            }

            $keyKodeJurusan = array_search('kodeJurusan', $fieldnames);
            if (is_int($keyKodeJurusan)) {
                $penjurusan = $em->getRepository('LanggasSisdikBundle:Penjurusan')
                    ->findOneBy([
                        'kode' => $content[$keyKodeJurusan],
                        'sekolah' => $sekolah,
                    ])
                ;

                if (!$penjurusan) {
                    $siswaKelas->setPenjurusan(null);
                } else {
                    $siswaKelas->setPenjurusan($penjurusan);
                }
            }

            $keyKeterangan = array_search('keterangan', $fieldnames);
            if (is_int($keyKeterangan)) {
                $siswaKelas->setKeterangan($content[$keyKeterangan]);
            }

            $keyAktif = array_search('aktif', $fieldnames);
            if (is_int($keyAktif)) {
                $siswaKelas->setAktif($content[$keyAktif]);
            } else {
                throw $this->createNotFoundException('Status aktif/non-aktif siswa di suatu kelas harus ditentukan.');
            }

            $keyHapus = array_search('hapus', $fieldnames);
            if (is_int($keyHapus) && isset($content[$keyHapus])) {
                if ($content[$keyHapus] == 1) {
                    $em->remove($siswaKelas);
                }
            } else {
                $em->persist($siswaKelas);
            }

            $this->siswaDitempatkanJumlah++;

            if ($andFlush) {
                $em->flush();
                $em->clear($siswaKelas);
            }
        }
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.penempatan.siswa.kelas', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
