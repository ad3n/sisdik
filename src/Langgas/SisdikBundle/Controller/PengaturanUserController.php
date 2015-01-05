<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use FOS\UserBundle\Doctrine\UserManager;
use Langgas\SisdikBundle\Entity\Staf;
use Langgas\SisdikBundle\Entity\Guru;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\WaliKelas;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/user")
 * @PreAuthorize("hasAnyRole('ROLE_SUPER_ADMIN', 'ROLE_ADMIN')")
 */
class PengaturanUserController extends Controller
{
    /**
     * @Route("/", name="settings_user")
     * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function indexAction()
    {
        $this->setCurrentMenu(1);

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cariuser');

        $querybuilder = $em->createQueryBuilder()
            ->select('user')
            ->from('LanggasSisdikBundle:User', 'user')
            ->orderBy('user.username', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchoption'] != '') {
                if ($searchdata['searchoption'] == 'unset') {
                    $querybuilder->andWhere("user.sekolah IS NULL");
                } else {
                    $querybuilder
                        ->andWhere("user.sekolah = :sekolah")
                        ->setParameter('sekolah', $searchdata['searchoption'])
                    ;
                }
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere('user.name LIKE ?1 OR user.username LIKE ?2 OR user.email LIKE ?3')
                    ->setParameter(1, "%{$searchdata['searchkey']}%")
                    ->setParameter(2, "%{$searchdata['searchkey']}%")
                    ->setParameter(3, "%{$searchdata['searchkey']}%")
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'form' => $searchform->createView(),
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/edit/{id}", name="settings_user_edit")
     * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function editAction(Request $request, $id)
    {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        /* @var $userManager UserManager */
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy([
            'id' => $id,
        ]);

        $form = $this->createForm('sisdik_useredit', $user, [
            'mode' => 1,
            'role_hierarchy' => $this->container->getParameter('security.role_hierarchy.roles'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $roleSelected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleSelected) || in_array('ROLE_GURU_PIKET', $roleSelected) || in_array('ROLE_WALI_KELAS', $roleSelected)) {
                    $guru = $em->getRepository('LanggasSisdikBundle:Guru')
                        ->findOneBy([
                            'username' => $user->getUsername(),
                        ])
                    ;
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setGuru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $guru->setSekolah($form->getData()->getSekolah());
                        $user->setGuru($guru);
                    }
                }

                if (!(in_array('ROLE_GURU', $roleSelected) || in_array('ROLE_GURU_PIKET', $roleSelected) || in_array('ROLE_WALI_KELAS', $roleSelected))) {
                    $user->setGuru(null);
                }

                if (in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_ADMIN', $roleSelected)) {
                    $staf = $em->getRepository('LanggasSisdikBundle:Staf')
                        ->findOneBy([
                            'username' => $user->getUsername(),
                        ])
                    ;
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setStaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $staf->setSekolah($form->getData()->getSekolah());
                        $user->setStaf($staf);
                    }
                }

                if (!(in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_ADMIN', $roleSelected))) {
                    $user->setStaf(null);
                }

                $userManager->updateUser($user);

                $user = $form->getData();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.updated', [
                        '%username%' => $user->getUsername(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('settings_user'));
            }
        }

        return [
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * @Route("/register/tanpa-sekolah", name="settings_user_register_noschool")
     * @Template("LanggasSisdikBundle:PengaturanUser:register.ns.html.twig")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function registerNoSchoolAction(Request $request)
    {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm('sisdik_registeruser', $user, [
            'mode' => 1,
            'role_hierarchy' => $this->container->getParameter('security.role_hierarchy.roles'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            $data = $form->getData();

            if (is_numeric($data->getUsername())) {
                $message = $this->get('translator')->trans('alert.username.numeric.forstudent');
                $form->get('username')->addError(new FormError($message));
            }

            if ($form->isValid()) {
                $user->setConfirmationToken(null);
                $user->setEnabled(true);

                $userManager->updateUser($user);

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.inserted', [
                        '%username%' => $user->getUsername(),
                    ]));

                return $this->redirect($this->generateUrl('settings_user'));
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/register/di-sekolah", name="settings_user_register_withschool")
     * @Template("LanggasSisdikBundle:PengaturanUser:register.ws.html.twig")
     */
    public function registerWithSchoolAction(Request $request)
    {
        if ($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $this->setCurrentMenu(1);
        } else {
            $this->setCurrentMenu(2);
        }

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm('sisdik_registeruser', $user, [
            'mode' => 2,
            'role_hierarchy' => $this->container->getParameter('security.role_hierarchy.roles'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            $data = $form->getData();

            if (is_numeric($data->getUsername())) {
                $message = $this->get('translator')->trans('alert.username.numeric.forstudent');
                $form->get('username')->addError(new FormError($message));
            }

            if ($form->isValid()) {
                $user->setConfirmationToken(null);
                $user->setEnabled(true);

                $roleSelected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleSelected) || in_array('ROLE_GURU_PIKET', $roleSelected) || in_array('ROLE_WALI_KELAS', $roleSelected)) {
                    $guru = $em->getRepository('LanggasSisdikBundle:Guru')
                        ->findOneBy([
                            'username' => $user->getUsername(),
                        ])
                    ;
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setGuru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $guru->setSekolah($form->getData()->getSekolah());
                        // TODO: $guru->setNama($user->getName());
                        $user->setGuru($guru);
                    }
                }

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_ADMIN', $roleSelected)) {
                    $staf = $em->getRepository('LanggasSisdikBundle:Staf')
                        ->findOneBy([
                            'username' => $user->getUsername(),
                        ])
                    ;
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setStaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $staf->setSekolah($form->getData()->getSekolah());
                        // TODO: $staf->setNama($user->getName());
                        $user->setStaf($staf);
                    }
                }

                $userManager->updateUser($user);

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.inserted', [
                        '%username%' => $user->getUsername(),
                    ]))
                ;

                if ($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
                    return $this->redirect($this->generateUrl('settings_user'));
                } else {
                    return $this->redirect($this->generateUrl('settings_user_inschool_list'));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/delete/{id}/{confirmed}", name="settings_user_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteAction($id, $confirmed)
    {
        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository('LanggasSisdikBundle:User');
        $user = $repository->find($id);
        $username = $user->getUsername();

        $userManager = $this->container->get('fos_user.user_manager');

        if ($confirmed == 1) {
            try {
                $userManager->deleteUser($user);

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.deleted', [
                        '%username%' => $username,
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('settings_user'));
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.settings.user.fail.delete', [
                '%username%' => $username,
            ]))
        ;

        return $this->redirect($this->generateUrl('settings_user'));
    }

    /**
     * @Route("/di-sekolah", name="settings_user_inschool_list")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolListAction(Request $request)
    {
        $this->setCurrentMenu(2);

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $searchcondition = '';
        $searchkey = '';

        $searchform = $this->createForm('sisdik_cari');

        $querybuilder = $em->createQueryBuilder()
            ->select('user')
            ->from('LanggasSisdikBundle:User', 'user')
            ->where("user.sekolah != ''")
            ->orderBy('user.username', 'ASC')
        ;

        $searchform->submit($request);
        $searchdata = $searchform->getData();
        if ($searchdata['searchkey'] != '') {
            $querybuilder
                ->where('user.name LIKE ?1')
                ->orWhere('user.username LIKE ?2')
                ->orWhere('user.email LIKE ?3')
                ->setParameter(1, "%{$searchdata['searchkey']}%")
                ->setParameter(2, "%{$searchdata['searchkey']}%")
                ->setParameter(3, "%{$searchdata['searchkey']}%")
            ;
        }

        if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('info', $this->get('translator')->trans('flash.settings.user.message1'))
            ;

            $querybuilder->andWhere("user.username != :username")->setParameter("username", $user->getUsername());
        } else {
            $sekolah = $user->getSekolah();
            if (!is_object($sekolah) || !$sekolah instanceof Sekolah) {
                throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
            }

            $querybuilder
                ->andWhere("user.sekolah = :sekolah")
                ->setParameter("sekolah", $sekolah->getId())
            ;
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'form' => $searchform->createView(),
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/di-sekolah/edit/{id}", name="settings_user_inschool_edit")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function inschoolEditAction(Request $request, $id)
    {
        $this->setCurrentMenu(2);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');

        /* @var $user User */
        $user = $userManager->findUserBy([
            'id' => $id,
        ]);

        $roleProperties = $user->getRoles();

        if ($user->getSekolah() !== null) {
            if (in_array('ROLE_SISWA', $roleProperties)) {
                $mode = 2;
            } else {
                $mode = 3;
            }
        }

        $form = $this->createForm('sisdik_useredit', $user, [
            'mode' => $mode,
            'role_hierarchy' => $this->container->getParameter('security.role_hierarchy.roles'),
        ]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $roleSelected = $form->getData()->getRoles();

                $ketuaPanitia = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')
                    ->findOneBy([
                        'ketuaPanitia' => $user,
                    ])
                ;
                if ($ketuaPanitia instanceof PanitiaPendaftaran) {
                    $user->addRole('ROLE_KETUA_PANITIA_PSB');
                }

                $panitiaPendaftaran = $em->createQueryBuilder()
                    ->select('COUNT(panitiaPendaftaran.id)')
                    ->from('LanggasSisdikBundle:PanitiaPendaftaran', 'panitiaPendaftaran')
                    ->where("panitiaPendaftaran.panitia LIKE ?1")
                    ->setParameter(1, '%"'.$user->getId().'"%')
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
                if ($panitiaPendaftaran > 0) {
                    $user->addRole('ROLE_PANITIA_PSB');
                }

                $waliKelas = $em->getRepository('LanggasSisdikBundle:WaliKelas')
                    ->findOneBy([
                        'user' => $user,
                    ])
                ;
                if ($waliKelas instanceof WaliKelas) {
                    $user->addRole('ROLE_WALI_KELAS');
                }

                if (!in_array('ROLE_SISWA', $roleSelected)) {
                    $user->setSiswa(null);
                }

                if (in_array('ROLE_GURU', $roleSelected)) {
                    $guru = $em->getRepository('LanggasSisdikBundle:Guru')->findOneBy([
                        'username' => $user->getUsername(),
                    ]);
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setGuru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $guru->setSekolah($user->getSekolah());
                        $user->setGuru($guru);
                    }
                }

                if (!in_array('ROLE_GURU', $roleSelected)) {
                    $user->setGuru(null);
                }

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_WALI_KELAS', $roleSelected) || in_array('ROLE_ADMIN', $roleSelected)) {
                    $staf = $em->getRepository('LanggasSisdikBundle:Staf')->findOneBy([
                        'username' => $user->getUsername(),
                    ]);
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setStaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $staf->setSekolah($user->getSekolah());
                        $user->setStaf($staf);
                    }
                }

                if (!(in_array('ROLE_KEPALA_SEKOLAH', $roleSelected) || in_array('ROLE_WALI_KELAS', $roleSelected) || in_array('ROLE_ADMIN', $roleSelected))) {
                    $user->setStaf(null);
                }

                $userManager->updateUser($user);

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.updated', [
                        '%username%' => $user->getUsername(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('settings_user_inschool_list'));
            }
        }

        return [
            'form' => $form->createView(),
            'id' => $id,
        ];
    }

    /**
     * @Route("/di-sekolah/delete/{id}/{confirmed}", name="settings_user_inschool_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolDeleteAction($id, $confirmed)
    {
        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');

        $repository = $em->getRepository('LanggasSisdikBundle:User');
        $user = $repository->find($id);
        $username = $user->getUsername();

        if ($confirmed == 1) {
            try {
                $userManager->deleteUser($user);

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.settings.user.deleted', [
                        '%username%' => $username,
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('settings_user_inschool_list'));
        }

        $this
            ->get('session')
            ->getFlashBag()
            ->add('error', $this->get('translator')->trans('flash.settings.user.fail.delete', [
                '%username%' => $username,
            ]))
        ;

        return $this->redirect($this->generateUrl('settings_user_inschool_list'));
    }

    private function setCurrentMenu($option = 1)
    {
        $translator = $this->get('translator');

        if ($option == 1) {
            $menu = $this->get('langgas_sisdik.menu.main');
            $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.alluser', [], 'navigations')]->setCurrent(true);
        } elseif ($option == 2) {
            $menu = $this->get('langgas_sisdik.menu.main');
            $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.user', [], 'navigations')]->setCurrent(true);
        }
    }
}
