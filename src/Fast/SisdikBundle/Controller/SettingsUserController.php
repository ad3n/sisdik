<?php
namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Controller\SekolahList;
use Fast\SisdikBundle\Entity\Staf;
use Fast\SisdikBundle\Entity\Guru;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Constraint;
use Fast\SisdikBundle\Form\UserRegisterFormType;
use Fast\SisdikBundle\Form\UserFormType;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * 
 * @author Ihsan Faisal
 * @Route("/user")
 *
 */
class SettingsUserController extends Controller
{
    /**
     * @Route("/", name="settings_user")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('settings_user_list'));
    }

    /**
     * @Template()
     * @Route("/list/{filter}", name="settings_user_list", defaults={"filter"="all"})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function listAction(Request $request, $filter) {
        $em = $this->getDoctrine()->getManager();

        $searchcondition = '';

        $searchform = $this->createForm(new SimpleSearchFormType());

        $searchform->bind($request);
        $searchdata = $searchform->getData();
        if ($searchdata['searchkey'] != '') {
            $searchcondition = "( u.username LIKE '%{$searchdata['searchkey']}%' OR u.name LIKE '%{$searchdata['searchkey']}%' OR u.email LIKE '%{$searchdata['searchkey']}%' )";
        }

        if ($filter == 'all') {
            $query = $em
                    ->createQuery(
                            "SELECT u FROM FastSisdikBundle:User u "
                                    . ($searchcondition != '' ? " WHERE $searchcondition " : '')
                                    . " ORDER BY u.username ASC");
        } else if ($filter == 'unset') {
            $query = $em
                    ->createQuery(
                            "SELECT u FROM FastSisdikBundle:User u WHERE u.idsekolah IS NULL "
                                    . ($searchcondition != '' ? " AND $searchcondition " : '')
                                    . " ORDER BY u.username ASC");
        } else {
            $query = $em
                    ->createQuery(
                            "SELECT u FROM FastSisdikBundle:User u JOIN u.idsekolah s WHERE u.idsekolah = '$filter' "
                                    . ($searchcondition != '' ? " AND $searchcondition " : '')
                                    . " ORDER BY u.username ASC");
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $this->get('request')->query->get('page', 1));

        $sekolahlist = new SekolahList($this->container);
        return array(
                'form' => $searchform->createView(), 'pagination' => $pagination,
                'filter' => $filter, 'schools' => $sekolahlist->buildSekolahUserList(),
        );
    }

    /**
     * @Template()
     * @Route("/edit/{filter}/{id}/{page}", name="settings_user_edit", defaults={"filter"="all","page"=1})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function editAction(Request $request, $id, $filter, $page) {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array(
                    'id' => $id
                ));

        $roleproperties = $user->getRoles();

        if ($user->getIdsekolah() !== NULL) {
            if (in_array('ROLE_SISWA', $roleproperties)) {
                $formoption = 2;
            } else {
                $formoption = 3;
            }
        } else {
            // role user and super admin
            $formoption = 1;
        }

        $form = $this->createForm(new UserFormType($this->container, $formoption), $user);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $roleselected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleselected)
                        || in_array('ROLE_GURU_PIKET', $roleselected)
                        || in_array('ROLE_WALI_KELAS', $roleselected)) {
                    $guru = $em->getRepository('FastSisdikBundle:Guru')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setIdguru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $guru->setIdsekolah($form->getData()->getIdsekolah());
                        $user->setIdguru($guru);
                    }
                }
                if (!(in_array('ROLE_GURU', $roleselected)
                        || in_array('ROLE_GURU_PIKET', $roleselected)
                        || in_array('ROLE_WALI_KELAS', $roleselected))) {
                    $user->setIdguru(null);
                }

                if (in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_ADMIN', $roleselected)) {
                    $staf = $em->getRepository('FastSisdikBundle:Staf')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setIdstaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $staf->setIdsekolah($form->getData()->getIdsekolah());
                        $user->setIdstaf($staf);
                    }
                }
                if (!(in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_ADMIN', $roleselected))) {
                    $user->setIdstaf(null);
                }

                $userManager->updateUser($user);

                $user = $form->getData();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.user.updated',
                                                array(
                                                    '%username%' => $user->getUsername()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_user_list',
                                                array(
                                                    'page' => $page, 'filter' => $filter
                                                )));
            }
        }

        return array(
            'form' => $form->createView(), 'id' => $id, 'page' => $page, 'filter' => $filter
        );
    }

    /**
     * @Route("/register/ns/{filter}", name="settings_user_register_noschool", defaults={"filter"="all"})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     * @Template("FastSisdikBundle:SettingsUser:register.ns.html.twig")
     */
    public function registerNoSchoolAction(Request $request, $filter) {
        $this->setCurrentMenu(1);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm(new UserRegisterFormType($this->container, 1), $user);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {

                $user->setConfirmationToken(null);
                $user->setEnabled(true);

                $userManager->updateUser($user);

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.user.inserted',
                                                array(
                                                    '%username%' => $user->getUsername()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_user_list',
                                                array(
                                                    'filter' => $filter
                                                )));
            }
        }

        return array(
            'form' => $form->createView(), 'filter' => $filter,
        );
    }

    /**
     * @Route("/register/ws/{filter}", name="settings_user_register_withschool", defaults={"filter"="all"})
     * @Secure(roles="ROLE_ADMIN")
     * @Template("FastSisdikBundle:SettingsUser:register.ws.html.twig")
     */
    public function registerWithSchoolAction(Request $request, $filter) {
        if ($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $this->setCurrentMenu(1);
        } else {
            $this->setCurrentMenu(2);
        }

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->createUser();

        $form = $this->createForm(new UserRegisterFormType($this->container, 2), $user);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {

                $user->setConfirmationToken(null);
                $user->setEnabled(true);

                $roleselected = $form->getData()->getRoles();

                if (in_array('ROLE_GURU', $roleselected)
                        || in_array('ROLE_GURU_PIKET', $roleselected)
                        || in_array('ROLE_WALI_KELAS', $roleselected)) {
                    $guru = $em->getRepository('FastSisdikBundle:Guru')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setIdguru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $guru->setIdsekolah($form->getData()->getIdsekolah());
                        // TODO: $guru->setNama($user->getName());
                        $user->setIdguru($guru);
                    }
                }

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_WAKIL_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_ADMIN', $roleselected)) {
                    $staf = $em->getRepository('FastSisdikBundle:Staf')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setIdstaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $staf->setIdsekolah($form->getData()->getIdsekolah());
                        // TODO: $staf->setNama($user->getName());
                        $user->setIdstaf($staf);
                    }
                }

                $userManager->updateUser($user);

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.user.inserted',
                                                array(
                                                    '%username%' => $user->getUsername()
                                                )));

                if ($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
                    return $this
                            ->redirect(
                                    $this
                                            ->generateUrl('settings_user_list',
                                                    array(
                                                        'filter' => $filter
                                                    )));
                } else {
                    return $this->redirect($this->generateUrl('settings_user_inschool_list'));
                }
            }
        }

        return array(
            'form' => $form->createView(), 'filter' => $filter,
        );
    }

    /**
     * @Route("/delete/{filter}/{id}/{confirmed}", name="settings_user_delete", defaults={"filter"="all","confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteAction($id, $filter, $confirmed) {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('FastSisdikBundle:User');
        $user = $repository->find($id);
        $username = $user->getUsername();

        if ($confirmed == 1) {
            $this->container->get('fos_user.user_manager')->deleteUser($user);

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.settings.user.deleted',
                                            array(
                                                '%username%' => $username
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_user_list',
                                            array(
                                                'filter' => $filter
                                            )));
        }

        // should be returned to the originated page
        $this->get('session')
                ->setFlash('error',
                        $this->get('translator')
                                ->trans('flash.settings.user.fail.delete',
                                        array(
                                            '%username%' => $username
                                        )));

        return $this
                ->redirect(
                        $this
                                ->generateUrl('settings_user_list',
                                        array(
                                            'filter' => $filter
                                        )));
    }

    /**
     * @Route("/deletesome/{filter}/{confirmed}", name="settings_user_delete_some", defaults={"filter"="all","confirmed"=0})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteManyAction(Request $request, $filter, $confirmed) {
        if ($request->getMethod() == 'POST') {
            $checks = $request->get('checks');
            $wherein = '';
            if (is_array($checks)) {
                foreach ($checks as $keys => $values) {
                    $wherein .= $keys . ',';
                }
                $wherein = preg_replace('/,$/', '', $wherein);

                $em = $this->getDoctrine()->getManager();
                $query = $em
                        ->createQuery("DELETE FastSisdikBundle:User u WHERE u.id IN ($wherein)");
                $query->execute();
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')->trans('flash.settings.user.some.deleted'));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_user_list',
                                                array(
                                                    'filter' => $filter
                                                )));
            } else {
                // better be returned to the originated page
                $this->get('session')
                        ->setFlash('error',
                                $this->get('translator')
                                        ->trans('flash.settings.user.fail.noselected'));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_user_list',
                                                array(
                                                    'filter' => $filter
                                                )));
            }
        } else {
            // better be returned to the originated page
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.settings.user.fail.deletesome'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('settings_user_list',
                                            array(
                                                'filter' => $filter
                                            )));
        }
    }

    /**
     * @Template()
     * @Route("/inschool/list", name="settings_user_inschool_list")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolListAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $searchcondition = '';
        $searchkey = '';

        $searchform = $this->createForm(new SimpleSearchFormType());

        $searchform->bind($request);
        $searchdata = $searchform->getData();
        if ($searchdata['searchkey'] != '') {
            $searchcondition = "( u.username LIKE '%{$searchdata['searchkey']}%' OR u.name LIKE '%{$searchdata['searchkey']}%' OR u.email LIKE '%{$searchdata['searchkey']}%' )";
        }

        if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            $this->get('session')
                    ->setFlash('info',
                            $this->get('translator')->trans('flash.settings.user.message1'));

            $query = $em
                    ->createQuery(
                            "SELECT u FROM FastSisdikBundle:User u
                            WHERE u.idsekolah != '' AND u.username != '{$user->getUsername()}' "
                                    . ($searchcondition != '' ? " AND $searchcondition " : '')
                                    . " ORDER BY u.username ASC");
        } else {
            $sekolah = $user->getIdsekolah();
            if (!is_object($sekolah) || !$sekolah instanceof Sekolah) {
                throw new AccessDeniedException(
                        $this->get('translator')->trans('exception.registertoschool'));
            }

            $query = $em
                    ->createQuery(
                            "SELECT u FROM FastSisdikBundle:User u
                            WHERE (u.idsekolah != '' AND u.idsekolah = '{$sekolah->getId()}') "
                                    . ($searchcondition != '' ? " AND $searchcondition " : '')
                                    . " ORDER BY u.username ASC");
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $this->get('request')->query->get('page', 1));

        return array(
                'form' => $searchform->createView(), 'pagination' => $pagination,
                'searchkey' => $searchkey
        );
    }

    /**
     * @Template()
     * @Route("/inschool/edit/{id}/{page}", name="settings_user_inschool_edit", defaults={"page"=1})
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolEditAction(Request $request, $id, $page) {
        $this->setCurrentMenu(2);

        $em = $this->getDoctrine()->getManager();

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array(
                    'id' => $id
                ));

        $roleproperties = $user->getRoles();

        if ($user->getIdsekolah() !== NULL) {
            if (in_array('ROLE_SISWA', $roleproperties)) {
                $formoption = 2;
            } else {
                $formoption = 3;
            }
        }

        $form = $this->createForm(new UserFormType($this->container, $formoption), $user);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $roleselected = $form->getData()->getRoles();

                if (!in_array('ROLE_SISWA', $roleselected)) {
                    $user->setIdsiswa(null);
                }

                if (in_array('ROLE_GURU', $roleselected)) {
                    $guru = $em->getRepository('FastSisdikBundle:Guru')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($guru) && $guru instanceof Guru) {
                        $user->setIdguru($guru);
                    } else {
                        $guru = new Guru();
                        $guru->setUsername($user->getUsername());
                        $user->setIdguru($guru);
                    }
                }
                if (!in_array('ROLE_GURU', $roleselected)) {
                    $user->setIdguru(null);
                }

                if (in_array('ROLE_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_WALI_KELAS', $roleselected)
                        || in_array('ROLE_ADMIN', $roleselected)) {
                    $staf = $em->getRepository('FastSisdikBundle:Staf')
                            ->findOneBy(
                                    array(
                                        'username' => $user->getUsername()
                                    ));
                    if (is_object($staf) && $staf instanceof Staf) {
                        $user->setIdstaf($staf);
                    } else {
                        $staf = new Staf();
                        $staf->setUsername($user->getUsername());
                        $user->setIdstaf($staf);
                    }
                }
                if (!(in_array('ROLE_KEPALA_SEKOLAH', $roleselected)
                        || in_array('ROLE_WALI_KELAS', $roleselected)
                        || in_array('ROLE_ADMIN', $roleselected))) {
                    $user->setIdstaf(null);
                }

                $userManager->updateUser($user);

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.settings.user.updated',
                                                array(
                                                    '%username%' => $user->getUsername()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_user_inschool_list',
                                                array(
                                                    'page' => $page
                                                )));
            }
        }

        return array(
            'form' => $form->createView(), 'id' => $id, 'page' => $page
        );
    }

    /**
     * @Route("/inschool/delete/{id}/{confirmed}", name="settings_user_inschool_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_ADMIN")
     */
    public function inschoolDeleteAction($id, $confirmed) {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('FastSisdikBundle:User');
        $user = $repository->find($id);
        $username = $user->getUsername();

        if ($confirmed == 1) {
            $this->container->get('fos_user.user_manager')->deleteUser($user);

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.settings.user.deleted',
                                            array(
                                                '%username%' => $username
                                            )));

            return $this->redirect($this->generateUrl('settings_user_inschool_list'));
        }

        // should be returned to the originated page
        $this->get('session')
                ->setFlash('error',
                        $this->get('translator')
                                ->trans('flash.settings.user.fail.delete',
                                        array(
                                            '%username%' => $username
                                        )));

        return $this->redirect($this->generateUrl('settings_user_inschool_list'));
    }

    private function setCurrentMenu($option = 1) {
        if ($option == 1) {
            $menu = $this->get('fast_sisdik.menu.main');
            $menu['headings.setting']['links.alluser']->setCurrent(true);
        } else if ($option == 2) {
            $menu = $this->get('fast_sisdik.menu.main');
            $menu['headings.setting']['links.user']->setCurrent(true);
        }
    }

    private function getBreadcrumb() {
        $menu = $this->get('fast_sisdik.menu.main');
        $currentitem = $menu['headings.setting']['links.user'];
        return $currentitem->getBreadcrumbsArray();
    }
}
