<?php
namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Filesystem\Filesystem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/laporan-kehadiran-siswa")
 * @PreAuthorize("hasRole('ROLE_GURU_PIKET') or hasRole('ROLE_GURU')")
 */
class KehadiranSiswaReportController extends Controller
{
    const DOCUMENTS_BASEDIR = "/documents/base/";
    const BASEFILE = "base.ods";
    const OUTPUTFILE = "laporan-kehadiran-siswa.";
    const DOCUMENTS_OUTPUTDIR = "uploads/sekolah/laporan-kehadiran/";

    /**
     * @Route("/", name="laporan-kehadiran-siswa")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:KehadiranSiswa:laporan.html.twig")
     */
    public function indexAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_kehadiransiswareportsearch');

        $hariIni = new \DateTime();
        $searchform->get('hinggaTanggal')->setData($hariIni);

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            throw $this->createNotFoundException($this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'));
        }

        return [
            'searchform' => $searchform->createView(),
            'tahunAkademik' => $tahunAkademik,
        ];
    }

    /**
     * @Route("/ekspor", name="laporan-kehadiran-siswa_ekspor")
     * @Method("POST")
     */
    public function eksporAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $em->getRepository('LanggasSisdikBundle:TahunAkademik')
            ->findOneBy([
                'aktif' => true,
                'sekolah' => $sekolah->getId(),
            ])
        ;

        if (!(is_object($tahunAkademik) && $tahunAkademik instanceof TahunAkademik)) {
            $return = [
                "error" => $this->get('translator')->trans('flash.tahun.akademik.tidak.ada.yang.aktif'),
            ];

            $return = json_encode($return);

            return new Response($return, 200, [
                'Content-Type' => 'application/json',
            ]);
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('kehadiranSiswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiranSiswa')
            ->leftJoin('kehadiranSiswa.siswa', 'siswa')
            ->where('kehadiranSiswa.sekolah = :sekolah')
            ->andWhere('kehadiranSiswa.tahunAkademik = :tahunAkademik')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;

        $searchform = $this->createForm('sisdik_kehadiransiswareportsearch');
        $searchform->submit($this->getRequest());
        $searchdata = $searchform->getData();

        if ($searchform->isValid()) {
            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder->andWhere('kehadiranSiswa.kelas = :kelas');
                $querybuilder->setParameter('kelas', $searchdata['kelas']);
            }

            $dariTanggal = $searchdata['dariTanggal'];
            $hinggaTanggal = $searchdata['hinggaTanggal'];

            if ($dariTanggal instanceof \DateTime && $hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('kehadiranSiswa.tanggal >= :dariTanggal AND kehadiranSiswa.tanggal <= :hinggaTanggal');
                $querybuilder->setParameter('dariTanggal', $dariTanggal->format("Y-m-d 00:00:00"));
                $querybuilder->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d 24:00:00"));
                $querybuilder->addOrderBy('kehadiranSiswa.tanggal', 'ASC');
            } elseif (!($dariTanggal instanceof \DateTime) && $hinggaTanggal instanceof \DateTime) {
                $querybuilder->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal');
                $querybuilder->setParameter('hinggaTanggal', $hinggaTanggal->format("Y-m-d"));
            } else {
                $hariIni = new \DateTime();
                $querybuilder->andWhere('kehadiranSiswa.tanggal = :hinggaTanggal');
                $querybuilder->setParameter('hinggaTanggal', $hariIni->format("Y-m-d"));
            }
        }

        $querybuilder
            ->addOrderBy('siswa.nomorInduk', 'ASC')
            ->addOrderBy('siswa.namaLengkap', 'ASC')
        ;

        $kehadiranSiswa = $querybuilder->getQuery()->getResult();

        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_BASEDIR . self::BASEFILE;
        $outputdir = self::DOCUMENTS_OUTPUTDIR;

        $filenameoutput = self::OUTPUTFILE . date("Y-m-d h:i") . ".sisdik";

        $outputfiletype = "ods";
        $extensiontarget = $extensionsource = ".$outputfiletype";
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $fs = new Filesystem();
        if (!$fs->exists($outputdir . $sekolah->getId() . '/')) {
            $fs->mkdir($outputdir . $sekolah->getId() . '/');
        }

        $documentsource = $outputdir . $sekolah->getId() . '/' . $filesource;
        $documenttarget = $outputdir . $sekolah->getId() . '/' . $filetarget;

        if ($outputfiletype == 'ods') {
            if (copy($documentbase, $documenttarget) === TRUE) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:KehadiranSiswa:laporan.xml.twig", [
                        'kehadiranSiswa' => $kehadiranSiswa,
                        'tahunAkademik' => $tahunAkademik,
                        'kelas' => $searchdata['kelas'],
                        'dariTanggal' => $searchdata['dariTanggal'],
                        'hinggaTanggal' => $searchdata['hinggaTanggal'],
                        'daftarStatusKehadiran' => JadwalKehadiran::getDaftarStatusKehadiran(),
                    ])
                );

                if ($ziparchive->close() === TRUE) {
                    $return = [
                        "redirectUrl" => $this->generateUrl("laporan-kehadiran-siswa_unduh", [
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

        $return = [
            "error" => $this->get('translator')->trans('errorinfo.tak.ada.kehadiran.siswa'),
        ];

        $return = json_encode($return);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/unduh/{filename}/{type}", name="laporan-kehadiran-siswa_unduh")
     * @Method("GET")
     */
    public function unduhAction($filename, $type = 'ods')
    {
        $sekolah = $this->isRegisteredToSchool();

        $filetarget = $filename;
        $documenttarget = self::DOCUMENTS_OUTPUTDIR . $sekolah->getId() . '/' . $filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $doc = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $doc);
        $response->headers->set('Content-Description', 'Laporan Pendaftaran');

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

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', array(), 'navigations')][$this->get('translator')->trans('links.laporan.kehadiran.siswa', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool()
    {
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
