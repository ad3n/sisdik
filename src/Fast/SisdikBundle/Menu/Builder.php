<?php
namespace Fast\SisdikBundle\Menu;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Matcher\Voter\UriVoter;
use Knp\Menu\Renderer\ListRenderer;
use Mopa\Bundle\BootstrapBundle\Navbar\AbstractNavbarMenuBuilder;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 *
 * @author Ihsan Faisal
 * note: navigations is the default translation domain defined in knp_menu.html.twig
 */
class Builder extends AbstractNavbarMenuBuilder
{
    private $container;

    /**
     *
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory, ContainerInterface $container) {
        parent::__construct($factory);

        $this->container = $container;
    }

    public function createMainMenu(Request $request) {
        $securityContext = $this->container->get('security.context');
        $translator = $this->container->get('translator');

        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav pull-right');

        if ($securityContext
                ->isGranted(
                        array(
                            new Expression('hasRole("ROLE_SUPER_ADMIN")')
                        ))) {

            $supersettings = $this->createDropdownMenuItem($menu, 'headings.pengaturan.sisdik');

            $supersettings
                    ->addChild('links.alluser',
                            array(
                                'route' => 'settings_user',
                            ));
            $supersettings
                    ->addChild('links.schools',
                            array(
                                'route' => 'settings_school_list'
                            ));
            $supersettings
                    ->addChild('links.layanansms',
                            array(
                                'route' => 'layanansms'
                            ));

            $supersettings
                    ->addChild('links.presencestatus',
                            array(
                                'route' => 'presence_status'
                            ));

            $supersettings
                    ->addChild('links.presenceschedule',
                            array(
                                'route' => 'presence_schedule'
                            ));
        }

        if ($securityContext
                ->isGranted(
                        array(
                            new Expression('hasRole("ROLE_ADMIN")')
                        ))) {

            $settings = $this->createDropdownMenuItem($menu, 'headings.setting');

            $settings
                    ->addChild('links.user',
                            array(
                                'route' => 'settings_user_inschool_list',
                            ));

            $settings
                    ->addChild('links.school',
                            array(
                                'route' => 'settings_specsch',
                            ));

            $settings
                    ->addChild('links.placement',
                            array(
                                'route' => 'settings_placement'
                            ));

            $settings
                    ->addChild('links.year',
                            array(
                                'route' => 'settings_year'
                            ));

            $settings
                    ->addChild('links.admissiongroup',
                            array(
                                'route' => 'settings_admissiongroup'
                            ));

            $settings
                    ->addChild('links.academicyear',
                            array(
                                'route' => 'academicyear'
                            ));

            $settings
                    ->addChild('links.smstemplate',
                            array(
                                'route' => 'sms_template'
                            ));

            $settings
                    ->addChild('links.smspendaftaran',
                            array(
                                'route' => 'smspendaftaran'
                            ));

            $settings
                    ->addChild('links.jenisdokumensiswa',
                            array(
                                'route' => 'jenisdokumensiswa'
                            ));

        }

        if ($securityContext
                ->isGranted(
                        array(
                            new Expression('hasAnyRole("ROLE_BENDAHARA", "ROLE_BENDAHARA_YAYASAN")')
                        ))) {
            // fees
            $fees = $this->createDropdownMenuItem($menu, 'headings.fee');

            $fees
                    ->addChild('links.fee.type',
                            array(
                                'route' => 'fee_type'
                            ));
            $fees
                    ->addChild('links.fee.registration',
                            array(
                                'route' => 'fee_registration'
                            ));
            $fees
                    ->addChild('links.fee.once',
                            array(
                                'route' => 'fee_once'
                            ));
            $fees
                    ->addChild('links.fee.recur',
                            array(
                                'route' => 'fee_recur'
                            ));

            $fees
                    ->addChild('links.reward.type',
                            array(
                                'route' => 'rewardtype'
                            ));

            $fees
                    ->addChild('links.reward.amount',
                            array(
                                'route' => 'rewardamount'
                            ));

        }

        $rolependaftaran = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_PANITIA_PSB", "ROLE_KETUA_PANITIA_PSB")';
        if ($securityContext
                ->isGranted(
                        array(
                            new Expression($rolependaftaran)
                        ))) {
            // pendaftaran
            $pendaftaran = $this->createDropdownMenuItem($menu, 'headings.pendaftaran');

            if ($securityContext
                    ->isGranted(
                            array(
                                    new Expression(
                                            "hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
                            ))) {
                $pendaftaran
                        ->addChild('links.regcommittee',
                                array(
                                    'route' => 'regcommittee'
                                ));
            }

            $pendaftaran
                    ->addChild('links.registration',
                            array(
                                'route' => 'applicant'
                            ));

            if ($securityContext
                    ->isGranted(
                            array(
                                    new Expression(
                                            'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_KETUA_PANITIA_PSB")')
                            ))) {
                $pendaftaran
                        ->addChild('links.tahkik',
                                array(
                                    'route' => 'tahkik'
                                ));
            }

            $pendaftaran
                    ->addChild('links.referensi',
                            array(
                                'route' => 'referensi'
                            ));

        }

        $roleakademik = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH")';
        if ($securityContext
                ->isGranted(
                        array(
                            new Expression($roleakademik)
                        ))) {
            // academic
            $academic = $this->createDropdownMenuItem($menu, 'headings.academic');

            $academic
                    ->addChild('links.data.academiccalendar',
                            array(
                                'route' => 'data_kaldemik'
                            ));

            $academic
                    ->addChild('links.tingkat',
                            array(
                                'route' => 'tingkat-kelas'
                            ));

            $academic
                    ->addChild('links.data.class',
                            array(
                                'route' => 'data_class'
                            ));

            $academic
                    ->addChild('links.data.classguardian',
                            array(
                                'route' => 'data_classguardian'
                            ));

            $academic
                    ->addChild('links.data.student',
                            array(
                                'route' => 'data_student'
                            ));
        }

        if ($securityContext
                ->isGranted(
                        array(
                            new Expression('hasAnyRole("ROLE_KETUA_PANITIA_PSB", "ROLE_BENDAHARA")')
                        ))) {
            // payments
            $payments = $this->createDropdownMenuItem($menu, 'headings.payments');

            $payments
                    ->addChild('links.applicant.payment',
                            array(
                                'route' => 'applicant_payment'
                            ));

            $payments
                    ->addChild('links.printreceiptsoption',
                            array(
                                'route' => 'printreceiptsoption'
                            ));

        }

        if ($securityContext
                ->isGranted(
                        array(
                            new Expression('hasAnyRole("ROLE_ADMIN", "ROLE_GURU", "ROLE_GURU_PIKET")')
                        ))) {
            // presence
            $presence = $this->createDropdownMenuItem($menu, 'headings.presence');

            if ($securityContext
                    ->isGranted(
                            array(
                                new Expression('hasRole("ROLE_ADMIN")')
                            ))) {
                $presence
                        ->addChild('links.attendancemachine',
                                array(
                                    'route' => 'attendancemachine'
                                ));

                $presence
                        ->addChild('links.presenceschedule',
                                array(
                                    'route' => 'presence_schedule_single'
                                ));

                //                 $presence
                //                         ->addChild('links.smsbulk',
                //                                 array(
                //                                     'uri' => '#no'
                //                                 ));
            }

            $presence
                    ->addChild('links.studentspresence',
                            array(
                                'route' => 'studentspresence'
                            ));
        }

        // heading.student
        //         $menu
        //                 ->addChild('headings.student',
        //                         array(
        //                             'uri' => '#nogo'
        //                         ));
        //         $menu['headings.student']->setAttribute('class', 'dropdown');
        //         $menu['headings.student']->setChildrenAttribute('class', 'dropdown-menu');
        //         $menu['headings.student']
        //                 ->setLinkAttributes(
        //                         array(
        //                             'class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'
        //                         ));
        //         $menu['headings.student']
        //                 ->addChild('info siswa',
        //                         array(
        //                             'uri' => '#nogo'
        //                         ));

        foreach ($menu as $key => $item) {
            $item
                    ->setExtra('routes',
                            array(
                                'routes' => $key
                            ));
        }

        return $menu;
    }
}
