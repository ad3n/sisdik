<?php
namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use FOS\UserBundle\Model\UserManager;
use Langgas\SisdikBundle\Entity\Staf;
use Langgas\SisdikBundle\Entity\Guru;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/user")
 * @PreAuthorize("hasAnyRole('ROLE_SUPER_ADMIN', 'ROLE_ADMIN')")
 */
class SettingsUserController extends Controller
{
    /**
     * @Route("/", name="settings_user")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cariuser');

        $querybuilder = $em->createQueryBuilder()
            ->select('u')
            ->from('LanggasSisdikBundle:User', 'u')
            ->orderBy('u.username', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchoption'] != '') {
                if ($searchdata['searchoption'] == 'unset') {
                    $querybuilder->andWhere("u.sekolah IS NULL");
                } else {
                    $querybuilder->leftJoin("u.sekolah", 't2');
                    $querybuilder->andWhere("u.sekolah = :sekolah");
                    $querybuilder->setParameter(':sekolah', $searchdata['searchoption']);
                }
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('u.name LIKE ?1 OR u.username LIKE ?2 OR u.email LIKE ?3');
                $querybuilder->setParameter(1, "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter(2, "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter(3, "%{$searchdata['searchkey']}%");
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
     * @Secure(roles="ROLE_SUPER_ADMIN")
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy([
            'id' => $id,
        ]);

        $roleproperties = $user->getRoles();

        $form = $this->createForm('sisdik_useredit', $user, ['mode' => 1]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $roleselected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleselected) || in_array('ROLE_GURU_PIKET', $roleselected) || in_array('ROLE_WALI_KELAS', $roleselected)) {
                    $guru = $em->getRepository('LanggasSisdikBundle:Guru')
                        ->findOneBy([
                            'username' => $user->getUsername()
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

                if (!(in_array('ROLE_GURU', $roleselected) || in_array('ROLE_GURU_PIKET', $roleselected) || in_array('ROLE_WALI_KELAS', $roleselected))) {
                    $user->setGuru(null);
                }

                if (in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_ADMIN', $roleselected)) {
                    $staf = $em->getRepository('LanggasSisdikBundle:Staf')
                        ->findOneBy([
                            'username' => $user->getUsername()
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

                if (!(in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_ADMIN', $roleselected))) {
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
     * @Route("/register/ns", name="settings_user_register_noschool")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     * @Template("LanggasSisdikBundle:SettingsUser:register.ns.html.twig")
     */
    public function registerNoSchoolAction(Request $request)
    {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm('sisdik_registeruser', $user, ['mode' => 1]);

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
     * @Route("/register/ws", name="settings_user_register_withschool")
     * @Template("LanggasSisdikBundle:SettingsUser:register.ws.html.twig")
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

        $form = $this->createForm('sisdik_registeruser', $user, ['mode' => 2]);

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

                $roleselected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleselected) || in_array('ROLE_GURU_PIKET', $roleselected) || in_array('ROLE_WALI_KELAS', $roleselected)) {
                    $guru = $em->getRepository('LanggasSisdikBundle:Guru')->findOneBy([
                        'username' => $user->getUsername(),
                    ]);
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

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_ADMIN', $roleselected)) {
                    $staf = $em->getRepository('LanggasSisdikBundle:Staf')->findOneBy([
                        'username' => $user->getUsername(),
                    ]);
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

        if ($confirmed == 1) {
            try {
                $this->container->get('fos_user.user_manager')->deleteUser($user);

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
     * @Template()
     * @Route("/inschool/list", name="settings_user_inschool_list")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolListAction(Request $request)
    {
        $this->setCurrentMenu(2);

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $searchcondition = '';
        $searchkey = '';

        $searchform = $this->createForm('cari');

        $querybuilder = $em->createQueryBuilder()
            ->select('u')
            ->from('LanggasSisdikBundle:User', 'u')
            ->where("u.sekolah != ''")
            ->orderBy('u.username', 'ASC')
        ;

        $searchform->submit($request);
        $searchdata = $searchform->getData();
        if ($searchdata['searchkey'] != '') {
            $querybuilder->where('u.name LIKE ?1');
            $querybuilder->orWhere('u.username LIKE ?2');
            $querybuilder->orWhere('u.email LIKE ?3');
            $querybuilder->setParameter(1, "%{$searchdata['searchkey']}%");
            $querybuilder->setParameter(2, "%{$searchdata['searchkey']}%");
            $querybuilder->setParameter(3, "%{$searchdata['searchkey']}%");
        }

        if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('info', $this->get('translator')->trans('flash.settings.user.message1'))
            ;

            $querybuilder->andWhere("u.username != :username")->setParameter("username", $user->getUsername());

        } else {
            $sekolah = $user->getSekolah();
            if (!is_object($sekolah) || !$sekolah instanceof Sekolah) {
                throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
            }

            $querybuilder
                ->andWhere("u.sekolah = :sekolah")
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
     * @Route("/inschool/edit/{id}/{page}", name="settings_user_inschool_edit", defaults={"page"=1})
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function inschoolEditAction(Request $request, $id, $page)
    {
        $this->setCurrentMenu(2);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy([
            'id' => $id,
        ]);

        $roleproperties = $user->getRoles();

        if ($user->getSekolah() !== NULL) {
            if (in_array('ROLE_SISWA', $roleproperties)) {
                $mode = 2;
            } else {
                $mode = 3;
            }
        }

        $form = $this->createForm('sisdik_useredit', $user, ['mode' => $mode]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $roleselected = $form->getData()->getRoles();

                if (!in_array('ROLE_SISWA', $roleselected)) {
                    $user->setSiswa(null);
                }

                if (in_array('ROLE_GURU', $roleselected)) {
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

                if (!in_array('ROLE_GURU', $roleselected)) {
                    $user->setGuru(null);
                }

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_WALI_KELAS', $roleselected) || in_array('ROLE_ADMIN', $roleselected)) {
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

                if (!(in_array('ROLE_KEPALA_SEKOLAH', $roleselected) || in_array('ROLE_WALI_KELAS', $roleselected) || in_array('ROLE_ADMIN', $roleselected))) {
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

                return $this->redirect($this->generateUrl('settings_user_inschool_list', [
                    'page' => $page,
                ]));
            }
        }

        return [
            'form' => $form->createView(),
            'id' => $id,
            'page' => $page,
        ];
    }

    /**
     * @Route("/inschool/delete/{id}/{confirmed}", name="settings_user_inschool_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolDeleteAction($id, $confirmed)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('LanggasSisdikBundle:User');
        $user = $repository->find($id);
        $username = $user->getUsername();

        if ($confirmed == 1) {
            try {
                $this->container->get('fos_user.user_manager')->deleteUser($user);

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
