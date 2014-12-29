<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Util\PasswordGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\FormError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/membuat-username-siswa")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class UserSiswaController extends Controller
{
    const DOCUMENTS_DIR = "/documents/";
    const BASEFILE = "base.ods";
    const OUTPUTPREFIX = "username-";
    const PYCONVERTER = "converter.py";
    const DOCUMENTS_BASEDIR = "base/";
    const DOCUMENTS_OUTPUTDIR = "output/";

    /**
     * @Route("/", name="siswa_generate_username")
     * @Template("LanggasSisdikBundle:Siswa:generate.username.html.twig")
     */
    public function generateUserAction()
    {
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_usersiswa');

        $request = $this->getRequest();
        if ($request->isMethod("POST")) {
            $form->submit($request);
            $data = $form->getData();

            if ($data['regenerate'] == true) {
                if ($data['filter'] == '' || !is_numeric($data['filter'])) {
                    $message = $this->get('translator')->trans('alert.filter.noempty.numeric');
                    $form->get('filter')->addError(new FormError($message));
                }
            }

            if ($form->isValid()) {
                $retval = $this->generateUsernamePasswordList($data['tahun'], $data['filter'], $data['output'], $data['regenerate']);
                if (is_array($retval) && array_key_exists('sessiondata', $retval)) {
                    return $this->redirect($this->generateUrl('siswa_generate_username_confirm', [
                        'file' => $retval['sessiondata'],
                        'type' => $retval['filetype'],
                        'regenerate' => $data['regenerate'],
                    ]));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/konfirmasi/{file}.{type}/{regenerate}", name="siswa_generate_username_confirm")
     * @Template("LanggasSisdikBundle:Siswa:generate.username.confirm.html.twig")
     */
    public function generateUserConfirmAction($file, $type, $regenerate = '')
    {
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_konfirmusersiswa', [], [
            'sessiondata' => $file,
        ]);

        $request = $this->getRequest();
        if ($request->isMethod("POST")) {
            $form->submit($request);
            if ($form->isValid()) {
                $sessiondata = $form['sessiondata']->getData();
                $credentials = $this->get('session')->get($sessiondata);

                if ($this->generateUsernamePassword($credentials, $regenerate)) {
                    $this
                        ->get('session')
                        ->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.student.username.populated'))
                    ;

                    return $this->redirect($this->generateUrl('siswa_generate_username'));
                }
            }
        }

        return [
            'form' => $form->createView(),
            'file' => $file,
            'type' => $type,
            'regenerate' => $regenerate,
        ];
    }

    /**
     * Download the generated file contains username-password list
     *
     * @Route("/unduh/{file}.{type}", name="siswa_generate_username_download")
     */
    public function downloadGeneratedFileAction($file, $type)
    {
        $filetarget = $file.'.'.$type;

        $documenttarget = $this->get('kernel')->getRootDir().self::DOCUMENTS_DIR.self::DOCUMENTS_OUTPUTDIR.$filetarget;

        $response = new Response(file_get_contents($documenttarget), 200);
        $d = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filetarget);
        $response->headers->set('Content-Disposition', $d);
        $response->headers->set('Content-Description', 'Username password siswa');

        if ($type == 'xls') {
            $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        } elseif ($type == 'ods') {
            $response->headers->set('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet');
        } else {
            $response->headers->set('Content-Type', 'application');
        }

        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Content-Length', filesize($documenttarget));

        return $response;
    }

    /**
     * Check if students username and password has already generated
     *
     * @Route("/ajax/periksa", name="siswa_ajax_generated_username")
     */
    public function ajaxCheckGeneratedUserAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $idtahun = $this->getRequest()->query->get('tahun');
        $nomorIndukSistem = $this->getRequest()->query->get('siswa');
        $regenerate = $this->getRequest()->query->get('regenerate');

        $tahun = $em->getRepository('LanggasSisdikBundle:Tahun')->find($idtahun);

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
            ->findOneBy([
                'nomorIndukSistem' => $nomorIndukSistem,
                'calonSiswa' => false,
            ])
        ;

        $retval = [];
        $siswa_identities = [];
        $info = '&nbsp;';
        if ($nomorIndukSistem != '' && is_object($siswa) && $siswa instanceof Siswa) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserBy([
                'username' => $siswa->getNomorIndukSistem(),
            ]);

            if (is_object($user) && $user instanceof User) {
                $linkstudent = $this->generateUrl("siswa_show", [
                    'id' => $siswa->getId(),
                ]);

                $linkuser = $this->generateUrl("settings_user_inschool_edit", [
                    'id' => $user->getId(),
                ]);

                $info = $this->get('translator')
                    ->trans('shortinfo.student.has.username', [
                        '%student%' => $siswa->getNamaLengkap().' ('.$siswa->getNomorIndukSistem().')',
                        '%linkstudent%' => $linkstudent,
                        '%user%' => $user->getUsername(),
                        '%linkuser%' => $linkuser,
                    ])
                ;

                $retval = [
                    'generated' => 'YES',
                    'partial' => 'NO',
                    'info' => $info,
                    'proceedpost' => $regenerate == 'YES' ? 'YES' : 'NO',
                ];
            } else {
                $linkstudent = $this->generateUrl("siswa_show", [
                    'id' => $siswa->getId(),
                ]);

                $info = $this->get('translator')
                    ->trans('shortinfo.student.hasno.username', [
                        '%student%' => $siswa->getNamaLengkap().' ('.$siswa->getNomorIndukSistem().')',
                        '%linkstudent%' => $linkstudent,
                    ])
                ;

                $retval = [
                    'generated' => 'NO',
                    'partial' => 'NO',
                    'info' => $info,
                    'proceedpost' => 'YES',
                ];
            }
        } elseif ($nomorIndukSistem != '' && is_null($siswa)) {
            // the filtered student doesn't exist!
            $info = $this->get('translator')
                ->trans('alert.student.noexists', [
                    '%filter%' => $nomorIndukSistem,
                ])
            ;

            $retval = [
                'generated' => 'NO',
                'partial' => 'NO',
                'info' => $info,
                'proceedpost' => 'NO',
            ];
        } else {
            $entities = $em->getRepository('LanggasSisdikBundle:Siswa')
                ->findBy([
                    'tahun' => $tahun,
                    'calonSiswa' => false,
                ])
            ;

            $siswa_num = count($entities);
            foreach ($entities as $entity) {
                $siswa_identities[] = $entity->getNomorIndukSistem();
            }

            if (count($siswa_identities) != 0) {
                $qbnum = $em->createQueryBuilder()
                    ->select('COUNT(user.id)')
                    ->from('LanggasSisdikBundle:User', 'user')
                    ->leftJoin('user.siswa', 'siswa')
                    ->where('user.siswa IS NOT NULL')
                    ->andWhere('siswa.tahun = :tahun')
                    ->andWhere('user.sekolah = :sekolah')
                    ->setParameter("tahun", $tahun)
                    ->setParameter("sekolah", $sekolah)
                ;
                $username_num = $qbnum->getQuery()->getSingleScalarResult();

                $qbduplication = $em->createQueryBuilder()
                    ->select('COUNT(user.id)')
                    ->from('LanggasSisdikBundle:User', 'user')
                    ->where('user.username IN (?1)')
                    ->setParameter(1, $siswa_identities)
                ;
                $duplicatedusername_num = $qbduplication->getQuery()->getSingleScalarResult();

                if ($siswa_num > $username_num && $username_num > 0) {
                    $diff_num = $siswa_num - $username_num;
                    $info = $this->get('translator')
                        ->trans('alert.username.partially.generated', [
                            '%year%' => $tahun->getTahun(),
                            '%num%' => $diff_num,
                            '%total%' => $siswa_num,
                        ])
                    ;
                    $retval = [
                        'generated' => 'YES',
                        'partial' => 'YES',
                        'info' => $info,
                        'proceedpost' => 'YES',
                    ];
                } elseif ($siswa_num > $username_num && $username_num == 0) {
                    $linktotal = $this->generateUrl("siswa")."?sisdik_carisiswa[tahun]=".$tahun->getId();

                    $info = $this->get('translator')
                        ->trans('shortinfo.username.not.generated', [
                            '%year%' => $tahun->getTahun(),
                            '%total%' => $siswa_num,
                            '%linktotal%' => $linktotal,
                        ])
                    ;

                    $retval = [
                        'generated' => 'NO',
                        'partial' => 'NO',
                        'info' => $info,
                        'proceedpost' => 'YES',
                    ];
                } elseif ($siswa_num == $username_num && $username_num > 0) {
                    $info = $this->get('translator')
                        ->trans('alert.username.fully.generated', [
                            '%year%' => $tahun->getTahun(),
                        ])
                    ;

                    $retval = [
                        'generated' => 'YES',
                        'partial' => 'NO',
                        'info' => $info,
                        'proceedpost' => 'NO',
                    ];
                } elseif ($siswa_num == 0 && $username_num == 0) {
                    $info = $this->get('translator')
                        ->trans('shortinfo.username.not.generated', [
                            '%year%' => $tahun->getTahun(),
                        ])
                    ;

                    $retval = [
                        'generated' => 'NO',
                        'partial' => 'NO',
                        'info' => $info,
                        'proceedpost' => 'YES',
                    ];
                } elseif ($siswa_num < $username_num && $username_num > 0) {
                    $info = $this->get('translator')
                        ->trans('alert.username.generated.bigger', [
                            '%year%' => $tahun->getTahun(),
                            '%num%' => $username_num,
                            '%total%' => $siswa_num,
                        ])
                    ;

                    $retval = [
                        'generated' => 'YES',
                        'partial' => 'NO',
                        'info' => $info,
                        'proceedpost' => 'YES',
                    ];
                }
            } else {
                $info = $this->get('translator')
                    ->trans('alert.username.nostudent', [
                        '%year%' => $tahun->getTahun(),
                    ])
                ;

                $retval = [
                    'generated' => 'NO',
                    'partial' => 'NO',
                    'info' => $info,
                    'proceedpost' => 'NO',
                ];
            }
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Mendapatkan nama siswa and nomorinduksistem
     *
     * @Route("/ajax/saring", name="siswa_ajax_filter_student")
     */
    public function ajaxFilterStudentAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $em = $this->getDoctrine()->getManager();

        $idtahun = $this->getRequest()->query->get('tahun');
        $filter = $this->getRequest()->query->get('filter');

        $tahun = $em->getRepository('LanggasSisdikBundle:Tahun')->find($idtahun);

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.tahun = :tahun')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere('siswa.nomorIndukSistem LIKE :filter OR siswa.namaLengkap LIKE :filter')
            ->setParameter("tahun", $tahun)
            ->setParameter("sekolah", $sekolah)
            ->setParameter("calon", false)
            ->setParameter('filter', "%$filter%")
        ;
        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            $retval[] = [
                'label' =>/** @Ignore */ $result->getNamaLengkap()." ({$result->getNomorIndukSistem()})",
                'value' => $result->getNomorIndukSistem(),
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     *
     * @param Tahun   $tahun
     * @param int     $penyaring
     * @param string  $outputfiletype
     * @param boolean $regenerate
     *
     * @return string $filename
     */
    private function generateUsernamePasswordList(Tahun $tahun, $penyaring, $outputfiletype = "ods", $regenerate = false)
    {
        $em = $this->getDoctrine()->getManager();

        $passwordargs = [
            'length' => 8,
            'alpha_upper_include' => true,
            'alpha_lower_include' => true,
            'number_include' => true,
            'symbol_include' => true,
        ];

        if ($penyaring != '') {
            // get filtered student
            $querybuilder = $em->createQueryBuilder()
                ->select('siswa')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->where('siswa.tahun = :tahun')
                ->setParameter('tahun', $tahun)
                ->andWhere('siswa.nomorIndukSistem = :nomorsistem')
                ->setParameter('nomorsistem', $penyaring)
                ->andWhere('siswa.calonSiswa = :calon')
                ->setParameter('calon', false)
            ;
            $results = $querybuilder->getQuery()->getResult();
        } else {
            $qbUsername = $em->createQueryBuilder()
                ->select('user.username')
                ->from('LanggasSisdikBundle:User', 'user')
                ->where('user.sekolah = :sekolah')
                ->andWhere('user.siswa IS NOT NULL')
                ->setParameter('sekolah', $tahun->getSekolah())
            ;
            $usernameTersimpan = $qbUsername->getQuery()->getArrayResult();

            if (count($usernameTersimpan) > 0) {
                $qbSiswa = $em->createQueryBuilder()
                    ->select('siswa')
                    ->from('LanggasSisdikBundle:Siswa', 'siswa')
                    ->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.calonSiswa = :calon')
                    ->andWhere('siswa.nomorIndukSistem NOT IN (?1)')
                    ->orderBy('siswa.nomorIndukSistem', 'ASC')
                    ->setParameter('tahun', $tahun)
                    ->setParameter('calon', false)
                    ->setParameter(1, array_map(function ($p) { return $p['username']; }, $usernameTersimpan))
                ;
                $results = $qbSiswa->getQuery()->getResult();
            } else {
                $qbSiswa = $em->createQueryBuilder()
                    ->select('siswa')
                    ->from('LanggasSisdikBundle:Siswa', 'siswa')
                    ->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.calonSiswa = :calon')
                    ->orderBy('siswa.nomorIndukSistem', 'ASC')
                    ->setParameter('tahun', $tahun)
                    ->setParameter('calon', false)
                ;
                $results = $qbSiswa->getQuery()->getResult();
            }
        }

        $outputusername = [];
        foreach ($results as $siswa) {
            if (is_object($siswa) && $siswa instanceof Siswa) {
                $passwordobject = new PasswordGenerator($passwordargs);

                $siswakelas = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
                    ->findOneBy([
                        'siswa' => $siswa,
                        'aktif' => true,
                    ])
                ;

                $kelas_key = (is_object($siswakelas) && $siswakelas instanceof SiswaKelas) ? $siswakelas->getKelas()->getUrutan() : 0;
                $kelas_val = (is_object($siswakelas) && $siswakelas instanceof SiswaKelas) ? $siswakelas->getKelas()->getNama() : '';

                $outputusername[$kelas_key.$siswa->getNomorIndukSistem()] = [
                    'nama' => $siswa->getNamaLengkap(),
                    'kelas' => $kelas_val,
                    'username' => $siswa->getNomorIndukSistem(),
                    'password' => $passwordobject->getPassword(),
                ];

                // sort by class name and eventually nomorIndukSistem
                ksort($outputusername);
            }
        }

        // base
        $documentbase = $this->get('kernel')->getRootDir().self::DOCUMENTS_DIR.self::DOCUMENTS_BASEDIR.self::BASEFILE;

        // source and target
        $extensionsource = ".ods";
        $extensiontarget = ".".$outputfiletype;

        $time = time();
        $patterns = ['/\s+/', '/\//'];
        $replacements = ['', '_'];
        $filenameoutput = self::OUTPUTPREFIX.preg_replace($patterns, $replacements, strtolower($tahun->getTahun())).$time;

        $this->get('session')->set($filenameoutput, $outputusername);
        $filesource = $filenameoutput.$extensionsource;
        $filetarget = $filenameoutput.$extensiontarget;

        $documentsource = $this->get('kernel')->getRootDir().self::DOCUMENTS_DIR.self::DOCUMENTS_OUTPUTDIR.$filesource;
        $documenttarget = $this->get('kernel')->getRootDir().self::DOCUMENTS_DIR.self::DOCUMENTS_OUTPUTDIR.$filetarget;

        if ($outputfiletype == 'ods') {
            // do not convert

            if (copy($documentbase, $documenttarget) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documenttarget);
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:Siswa:username.xml.twig", [
                    'users' => $outputusername,
                ]));

                if ($ziparchive->close() === true) {
                    return [
                        'sessiondata' => $filenameoutput,
                        'filetype' => $outputfiletype,
                    ];
                }
            }
        } else {
            // convert from ods to target

            if (copy($documentbase, $documentsource) === true) {
                $ziparchive = new \ZipArchive();
                $ziparchive->open($documentsource);
                $ziparchive->addFromString('content.xml', $this->renderView("LanggasSisdikBundle:Siswa:username.xml.twig", [
                    'users' => $outputusername,
                ]));

                if ($ziparchive->close() === true) {
                    $scriptlocation = $this->get('kernel')->getRootDir().self::DOCUMENTS_DIR.self::PYCONVERTER;
                    exec("python $scriptlocation $documentsource $documenttarget");

                    return [
                        'sessiondata' => $filenameoutput,
                        'filetype' => $outputfiletype,
                    ];
                }
            }
        }

        return false;
    }

    /**
     *
     * @param array $credentials
     */
    private function generateUsernamePassword($credentials, $regenerate)
    {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->container->get('fos_user.user_manager');

        foreach ($credentials as $key => $value) {
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')
                ->findOneBy([
                    'nomorIndukSistem' => $value['username'],
                ])
            ;

            if (is_object($siswa) && $siswa instanceof Siswa) {
                if ($regenerate != 1) {
                    $user = $userManager->createUser();
                    $user->setUsername($siswa->getNomorIndukSistem());
                    $user->setPlainPassword($value['password']);

                    $user->setEmail($siswa->getNomorIndukSistem().'-'.$siswa->getEmail());
                    $user->setName($siswa->getNamaLengkap());
                    $user->addRole('ROLE_SISWA');
                    $user->setSiswa($siswa);
                    $user->setSekolah($siswa->getSekolah());
                    $user->setConfirmationToken(null);
                    $user->setEnabled(true);

                    $userManager->updateUser($user);
                } else {
                    $user = $userManager->findUserByUsername($siswa->getNomorIndukSistem());
                    if ($user instanceof User) {
                        $user->setPlainPassword($value['password']);
                        $userManager->updateUser($user);
                    } else {
                        $user = $userManager->createUser();
                        $user->setUsername($siswa->getNomorIndukSistem());
                        $user->setPlainPassword($value['password']);

                        $user->setEmail($siswa->getNomorIndukSistem().'-'.$siswa->getEmail());
                        $user->setName($siswa->getNamaLengkap());
                        $user->addRole('ROLE_SISWA');
                        $user->setSiswa($siswa);
                        $user->setSekolah($siswa->getSekolah());
                        $user->setConfirmationToken(null);
                        $user->setEnabled(true);

                        $userManager->updateUser($user);
                    }
                }
            }
        }

        return true;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.siswa', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
