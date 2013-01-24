<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Form\FormError;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Fast\SisdikBundle\Form\SiswaSearchType;
use Fast\SisdikBundle\Entity\Tahunmasuk;
use Fast\SisdikBundle\Util\PasswordGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\SiswaKelas;
use Fast\SisdikBundle\Form\SiswaGenerateUsernameType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * SiswaUsername controller. Manage students' username.
 *
 * @Route("/data/student/username")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class SiswaUsernameController extends Controller
{
    const DOCUMENTS_DIR = "/documents/";
    const BASEFILE = "base.ods";
    const OUTPUTPREFIX = "username-";
    const PYCONVERTER = "converter.py";
    const DOCUMENTS_BASEDIR = "base/";
    const DOCUMENTS_OUTPUTDIR = "output/";

    /**
     * Generate student usernames
     *
     * @Route("/", name="data_student_generate_username")
     * @Template("FastSisdikBundle:Siswa:generate.username.html.twig")
     */
    public function generateUsernameAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaGenerateUsernameType($this->container));

        $request = $this->getRequest();
        if ($request->isMethod("POST")) {
            $form->bind($request);
            $data = $form->getData();

            if ($data['regenerate'] == TRUE) {
                if ($data['filter'] == '' || !is_numeric($data['filter'])) {
                    $message = $this->get('translator')
                            ->trans('alert.filter.noempty.numeric');
                    $form->get('filter')->addError(new FormError($message));
                }
            }

            if ($form->isValid()) {
                $this
                        ->generateUsernamePasswordList($data['tahunmasuk'],
                                $data['filter'], $data['output'], $data['regenerate']);
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * Check if students username and password has already generated
     *
     * @Route("/ajax/checkgeneratedusername", name="data_student_ajax_generated_username")
     */
    public function ajaxCheckGeneratedUsernameAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $em = $this->getDoctrine()->getManager();

        $idtahunmasuk = $this->getRequest()->query->get('tahunmasuk');
        $nomorIndukSistem = $this->getRequest()->query->get('siswa');
        $regenerate = $this->getRequest()->query->get('regenerate');

        $tahunmasuk = $em->getRepository('FastSisdikBundle:Tahunmasuk')
                ->find($idtahunmasuk);
        $siswa = $em->getRepository('FastSisdikBundle:Siswa')
                ->findOneBy(
                        array(
                            'nomorIndukSistem' => $nomorIndukSistem,
                        ));

        $retval = array();
        $siswa_identities = '';
        $info = '&nbsp;';
        if ($nomorIndukSistem != '' && is_object($siswa) && $siswa instanceof Siswa) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager
                    ->findUserBy(
                            array(
                                'username' => $siswa->getNomorIndukSistem()
                            ));

            if (is_object($user) && $user instanceof User) {
                $linkstudent = $this
                        ->generateUrl("data_student_show",
                                array(
                                    'id' => $siswa->getId()
                                ));

                $searchtype = new SimpleSearchFormType();
                $linkuser = $this
                        ->generateUrl("settings_user_inschool_edit",
                                array(
                                    'id' => $user->getId()
                                ));

                $info = $this->get('translator')
                        ->trans('shortinfo.student.has.username',
                                array(
                                        '%student%' => $siswa->getNamaLengkap() . ' ('
                                                . $siswa->getNomorIndukSistem() . ')',
                                        '%linkstudent%' => $linkstudent,
                                        '%user%' => $user->getUsername(),
                                        '%linkuser%' => $linkuser,
                                ));
                $retval = array(
                        'generated' => 'YES', 'partial' => 'NO', 'info' => $info,
                        'proceedpost' => $regenerate == 'YES' ? 'YES' : 'NO'
                );
            } else {
                $linkstudent = $this
                        ->generateUrl("data_student_show",
                                array(
                                    'id' => $siswa->getId()
                                ));

                $info = $this->get('translator')
                        ->trans('shortinfo.student.hasno.username',
                                array(
                                        '%student%' => $siswa->getNamaLengkap() . ' ('
                                                . $siswa->getNomorIndukSistem() . ')',
                                        '%linkstudent%' => $linkstudent
                                ));
                $retval = array(
                        'generated' => 'NO', 'partial' => 'NO', 'info' => $info,
                        'proceedpost' => 'YES'
                );
            }

        } else if ($nomorIndukSistem != '' && is_null($siswa)) {
            // the filtered student doesn't exist!
            $info = $this->get('translator')
                    ->trans('alert.student.noexists',
                            array(
                                '%filter%' => $nomorIndukSistem
                            ));
            $retval = array(
                    'generated' => 'NO', 'partial' => 'NO', 'info' => $info,
                    'proceedpost' => 'NO'
            );
        } else {
            $entities = $em->getRepository('FastSisdikBundle:Siswa')
                    ->findBy(
                            array(
                                'tahunmasuk' => $tahunmasuk
                            ));

            $siswa_num = count($entities);
            foreach ($entities as $entity) {
                $siswa_identities .= $entity->getNomorIndukSistem() . ",";
            }
            $siswa_identities = preg_replace('/,$/', '', $siswa_identities);

            if ($siswa_identities != '') {
                $query = $em
                        ->createQuery(
                                "SELECT COUNT(t.id) FROM FastSisdikBundle:FosUser t "
                                        . " JOIN t.siswa t1 "
                                        . " WHERE t.siswa IS NOT NULL "
                                        . " AND t1.tahunmasuk = :tahunmasuk "
                                        . " AND t.sekolah = :sekolah ");
                $query->setParameter("tahunmasuk", $tahunmasuk);
                $query->setParameter("sekolah", $sekolah);
                $username_num = $query->getSingleScalarResult();

                $queryduplication = $em
                        ->createQuery(
                                "SELECT COUNT(t.id) FROM FastSisdikBundle:FosUser t "
                                        . " WHERE t.username IN ($siswa_identities) ");
                $duplicatedusername_num = $queryduplication->getSingleScalarResult();

                if ($siswa_num > $username_num && $username_num > 0) {
                    $diff_num = $siswa_num - $username_num;
                    $info = $this->get('translator')
                            ->trans('alert.username.partially.generated',
                                    array(
                                            '%year%' => $tahunmasuk->getTahun(),
                                            '%num%' => $diff_num,
                                            '%total%' => $siswa_num
                                    ));
                    $retval = array(
                            'generated' => 'YES', 'partial' => 'YES', 'info' => $info,
                            'proceedpost' => 'YES'
                    );
                } elseif ($siswa_num > $username_num && $username_num == 0) {
                    $searchtype = new SiswaSearchType($this->container);
                    $linktotal = $this->generateUrl("data_student")
                            . "?{$searchtype->getName()}[tahunmasuk]="
                            . $tahunmasuk->getId();

                    $info = $this->get('translator')
                            ->trans('shortinfo.username.not.generated',
                                    array(
                                            '%year%' => $tahunmasuk->getTahun(),
                                            '%total%' => $siswa_num,
                                            '%linktotal%' => $linktotal,
                                    ));

                    $retval = array(
                            'generated' => 'NO', 'partial' => 'NO', 'info' => $info,
                            'proceedpost' => 'YES'
                    );
                } elseif ($siswa_num == $username_num && $username_num > 0) {
                    $info = $this->get('translator')
                            ->trans('alert.username.fully.generated',
                                    array(
                                        '%year%' => $tahunmasuk->getTahun()
                                    ));
                    $retval = array(
                            'generated' => 'YES', 'partial' => 'NO', 'info' => $info,
                            'proceedpost' => 'NO'
                    );
                } elseif ($siswa_num == 0 && $username_num == 0) {
                    $info = $this->get('translator')
                            ->trans('shortinfo.username.not.generated',
                                    array(
                                        '%year%' => $tahunmasuk->getTahun()
                                    ));
                    $retval = array(
                            'generated' => 'NO', 'partial' => 'NO', 'info' => $info,
                            'proceedpost' => 'YES'
                    );
                } elseif ($siswa_num < $username_num && $username_num > 0) {
                    $info = $this->get('translator')
                            ->trans('alert.username.generated.bigger',
                                    array(
                                            '%year%' => $tahunmasuk->getTahun(),
                                            '%num%' => $username_num,
                                            '%total%' => $siswa_num
                                    ));
                    $retval = array(
                            'generated' => 'YES', 'partial' => 'NO', 'info' => $info,
                            'proceedpost' => 'YES'
                    );
                }
            } else {
                $info = $this->get('translator')
                        ->trans('alert.username.nostudent',
                                array(
                                    '%year%' => $tahunmasuk->getTahun()
                                ));
                $retval = array(
                        'generated' => 'NO', 'partial' => 'NO', 'info' => $info,
                        'proceedpost' => 'NO'
                );
            }
        }

        return new Response(json_encode($retval), 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * get student name and nomorinduksistem through ajax
     *
     * @Route("/ajax/filterstudent", name="data_student_ajax_filter_student")
     */
    public function ajaxFilterStudentAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $em = $this->getDoctrine()->getManager();

        $idtahunmasuk = $this->getRequest()->query->get('tahunmasuk');
        $filter = $this->getRequest()->query->get('filter');

        $tahunmasuk = $em->getRepository('FastSisdikBundle:Tahunmasuk')
                ->find($idtahunmasuk);

        $query = $em
                ->createQuery(
                        "SELECT t FROM FastSisdikBundle:Siswa t "
                                . " WHERE t.tahunmasuk = :tahunmasuk "
                                . " AND t.sekolah = :sekolah "
                                . " AND (t.nomorIndukSistem LIKE :filter OR t.namaLengkap LIKE :filter) ");
        $query->setParameter("tahunmasuk", $tahunmasuk);
        $query->setParameter("sekolah", $sekolah);
        $query->setParameter('filter', '%' . $filter . '%');
        $results = $query->getResult();

        $retval = array();
        foreach ($results as $result) {
            $retval[] = array(
                    'label' => $result->getNamaLengkap()
                            . " ({$result->getNomorIndukSistem()})",
                    'value' => $result->getNomorIndukSistem(),
            );
        }

        return new Response(json_encode($retval), 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * 
     * @param Tahunmasuk $tahunmasuk
     * @param int $penyaring
     * @param string $outputfiletype
     * @param boolean $regenerate
     * 
     * @return string $filename
     */
    private function generateUsernamePasswordList($tahunmasuk, $penyaring,
            $outputfiletype = "ods", $regenerate = FALSE) {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->container->get('fos_user.user_manager');

        $passwordargs = array(
                'length' => 8, 'alpha_upper_include' => TRUE,
                'alpha_lower_include' => TRUE, 'number_include' => TRUE,
                'symbol_include' => TRUE,
        );

        if ($penyaring != '') {
            // get filtered student
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Siswa', 't')
                    ->where('t.tahunmasuk = :tahunmasuk')
                    ->andWhere('t.nomorIndukSistem = :nomorsistem')
                    ->setParameter('tahunmasuk', $tahunmasuk->getId())
                    ->setParameter('nomorsistem', $penyaring);
            $results = $querybuilder->getQuery()->getResult();
        } else {
            // get students in a year
            // group by class?
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Siswa', 't')
                    ->where('t.tahunmasuk = :tahunmasuk')
                    ->orderBy('t.nomorIndukSistem', 'ASC')
                    ->setParameter('tahunmasuk', $tahunmasuk->getId());
            $results = $querybuilder->getQuery()->getResult();
        }

        $outputusername = array();
        foreach ($results as $siswa) {
            if (is_object($siswa) && $siswa instanceof Siswa) {
                $passwordobject = new PasswordGenerator($passwordargs);

                $siswakelas = $em->getRepository('FastSisdikBundle:SiswaKelas')
                        ->findOneBy(
                                array(
                                    'siswa' => $siswa, 'aktif' => TRUE
                                ));
                $kelas = (is_object($siswakelas) && $siswakelas instanceof SiswaKelas) ? $siswakelas
                                ->getKelas()->getNama() : '';

                $outputusername[] = array(
                        'nama' => $siswa->getNamaLengkap(), 'kelas' => $kelas,
                        'username' => $siswa->getNomorIndukSistem(),
                        'password' => $passwordobject->getPassword()
                );

                // $user = $userManager->createUser();
                // $user->setUsername($siswa->getNomorIndukSistem());
                // $user->setPlainPassword($passwordobject->getPassword());
                // $user
                //         ->setEmail(
                //                 $siswa->getNomorIndukSistem() . '-' . $siswa->getEmail());
                // $user->setName($siswa->getNamaLengkap());
                // $user->addRole('ROLE_SISWA');
                // $user->setSiswa($siswa);
                // $user->setSekolah($siswa->getSekolah());
                // $user->setConfirmationToken(null);
                // $user->setEnabled(true);

                // $userManager->updateUser($user);
            }
        }

        // base
        $documentbase = $this->get('kernel')->getRootDir() . self::DOCUMENTS_DIR
                . self::DOCUMENTS_BASEDIR . self::BASEFILE;

        // origin, source, and target
        $extensionorigin = ".csv";
        $extensionsource = ".ods";
        $extensiontarget = "." . $outputfiletype;

        $filenameoutput = self::OUTPUTPREFIX
                . preg_replace('/\s+/', '', strtolower($tahunmasuk->getTahun())) . time();

        $fileorigin = $filenameoutput . $extensionorigin;
        $filesource = $filenameoutput . $extensionsource;
        $filetarget = $filenameoutput . $extensiontarget;

        $documentorigin = $this->get('kernel')->getRootDir() . self::DOCUMENTS_DIR
                . self::DOCUMENTS_OUTPUTDIR . $fileorigin;
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
                                                    'users' => $outputusername,
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
                                                    'users' => $outputusername,
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

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.data.academic']['links.data.student']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')
                ->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.useadmin.or.headmaster'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
