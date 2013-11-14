<?php
namespace Fast\SisdikBundle\Menu;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Renderer\ListRenderer;
use Mopa\Bundle\BootstrapBundle\Navbar\AbstractNavbarMenuBuilder;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 *
 * @author Ihsan Faisal
 *         note: navigations is the default translation domain defined in knp_menu.html.twig
 */
class Builder extends AbstractNavbarMenuBuilder
{

    private $container;

    /**
     *
     * @param FactoryInterface $factory
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $container)
    {
        parent::__construct($factory);

        $this->container = $container;
    }

    public function createMainMenu(
        Request $request)
    {
        $securityContext = $this->container->get('security.context');
        $translator = $this->container->get('translator');

        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav pull-right');

        if ($securityContext->isGranted(array(
            new Expression(
                'hasRole("ROLE_SUPER_ADMIN")')
        ))) {

            $supersettings = $this->createDropdownMenuItem($menu, $translator->trans('headings.pengaturan.sisdik', array(), 'navigations'));

            $supersettings->addChild($translator->trans('links.alluser', array(), 'navigations'), array(
                'route' => 'settings_user'
            ));
            $supersettings->addChild($translator->trans('links.schools', array(), 'navigations'), array(
                'route' => 'settings_school_list'
            ));
            $supersettings->addChild($translator->trans('links.layanansms', array(), 'navigations'), array(
                'route' => 'layanansms'
            ));

            // $supersettings
            // ->addChild('links.presenceschedule',
            // array(
            // 'route' => 'presence_schedule'
            // ));
        }

        if ($securityContext->isGranted(array(
            new Expression(
                'hasRole("ROLE_ADMIN")')
        ))) {

            $settings = $this->createDropdownMenuItem($menu, $translator->trans('headings.setting', array(), 'navigations'));

            $settings->addChild($translator->trans('links.user', array(), 'navigations'), array(
                'route' => 'settings_user_inschool_list'
            ));

            $settings->addChild($translator->trans('links.school', array(), 'navigations'), array(
                'route' => 'settings_specsch'
            ));

            $settings->addChild($translator->trans('links.placement', array(), 'navigations'), array(
                'route' => 'settings_placement'
            ));

            $settings->addChild($translator->trans('links.year', array(), 'navigations'), array(
                'route' => 'settings_year'
            ));

            $settings->addChild($translator->trans('links.admissiongroup', array(), 'navigations'), array(
                'route' => 'settings_admissiongroup'
            ));

            $settings->addChild($translator->trans('links.academicyear', array(), 'navigations'), array(
                'route' => 'academicyear'
            ));

            $settings->addChild($translator->trans('links.smstemplate', array(), 'navigations'), array(
                'route' => 'sms_template'
            ));

            $settings->addChild($translator->trans('links.smspendaftaran', array(), 'navigations'), array(
                'route' => 'smspendaftaran'
            ));

            $settings->addChild($translator->trans('links.jenisdokumensiswa', array(), 'navigations'), array(
                'route' => 'jenisdokumensiswa'
            ));
        }

        if ($securityContext->isGranted(array(
            new Expression(
                'hasAnyRole("ROLE_BENDAHARA")')
        ))) {
            // fees
            $fees = $this->createDropdownMenuItem($menu, $translator->trans('headings.fee', array(), 'navigations'));

            $fees->addChild($translator->trans('links.fee.type', array(), 'navigations'), array(
                'route' => 'fee_type'
            ));
            $fees->addChild($translator->trans('links.fee.registration', array(), 'navigations'), array(
                'route' => 'fee_registration'
            ));
            $fees->addChild($translator->trans('links.fee.once', array(), 'navigations'), array(
                'route' => 'fee_once'
            ));
            $fees->addChild($translator->trans('links.fee.recur', array(), 'navigations'), array(
                'route' => 'fee_recur'
            ));

            $fees->addChild($translator->trans('links.reward.type', array(), 'navigations'), array(
                'route' => 'rewardtype'
            ));

            $fees->addChild($translator->trans('links.reward.amount', array(), 'navigations'), array(
                'route' => 'rewardamount'
            ));
        }

        $rolependaftaran = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_PANITIA_PSB", "ROLE_KETUA_PANITIA_PSB")';
        if ($securityContext->isGranted(array(
            new Expression(
                $rolependaftaran)
        ))) {
            // pendaftaran
            $pendaftaran = $this->createDropdownMenuItem($menu, $translator->trans('headings.pendaftaran', array(), 'navigations'));

            if ($securityContext->isGranted(array(
                new Expression(
                    "hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
            ))) {
                $pendaftaran->addChild($translator->trans('links.regcommittee', array(), 'navigations'), array(
                    'route' => 'regcommittee'
                ));
            }

            $pendaftaran->addChild($translator->trans('links.registration', array(), 'navigations'), array(
                'route' => 'applicant'
            ));

            $pendaftaran->addChild($translator->trans('links.laporan.pendaftaran', array(), 'navigations'), array(
                'route' => 'laporan-pendaftaran'
            ));

            $pendaftaran->addChild($translator->trans('links.sekolahasal', array(), 'navigations'), array(
                'route' => 'sekolahasal'
            ));

            $pendaftaran->addChild($translator->trans('links.referensi', array(), 'navigations'), array(
                'route' => 'referensi'
            ));

            if ($securityContext->isGranted(array(
                new Expression(
                    'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_KETUA_PANITIA_PSB")')
            ))) {
                $pendaftaran->addChild($translator->trans('links.tahkik', array(), 'navigations'), array(
                    'route' => 'tahkik'
                ));
            }
        }

        $roleakademik = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH")';
        if ($securityContext->isGranted(array(
            new Expression(
                $roleakademik)
        ))) {
            // academic
            $academic = $this->createDropdownMenuItem($menu, $translator->trans('headings.academic', array(), 'navigations'));

            $academic->addChild($translator->trans('links.data.academiccalendar', array(), 'navigations'), array(
                'route' => 'data_kaldemik'
            ));

            $academic->addChild($translator->trans('links.tingkat', array(), 'navigations'), array(
                'route' => 'tingkat-kelas'
            ));

            $academic->addChild($translator->trans('links.data.class', array(), 'navigations'), array(
                'route' => 'data_class'
            ));

            $academic->addChild($translator->trans('links.data.classguardian', array(), 'navigations'), array(
                'route' => 'data_classguardian'
            ));

            $academic->addChild($translator->trans('links.siswa', array(), 'navigations'), array(
                'route' => 'siswa'
            ));

            $academic->addChild($translator->trans('links.penempatan.siswa.kelas', array(), 'navigations'), array(
                'route' => 'penempatan-siswa-kelas'
            ));
        }

        if ($securityContext->isGranted(array(
            new Expression(
                'hasAnyRole("ROLE_BENDAHARA", "ROLE_KASIR")')
        ))) {
            // payments
            $payments = $this->createDropdownMenuItem($menu, $translator->trans('headings.payments', array(), 'navigations'));

            $payments->addChild($translator->trans('links.applicant.payment', array(), 'navigations'), array(
                'route' => 'applicant_payment'
            ));

            if ($securityContext->isGranted(array(
                new Expression(
                    "hasAnyRole('ROLE_BENDAHARA')")
            ))) {
                $payments->addChild($translator->trans('links.laporan.transaksi.keuangan', array(), 'navigations'), array(
                    'route' => 'laporan-transaksi-keuangan'
                ));
            }

            $payments->addChild($translator->trans('links.laporan.pembayaran.pendaftaran', array(), 'navigations'), array(
                'route' => 'laporan-pembayaran-pendaftaran'
            ));

            $payments->addChild($translator->trans('links.printreceiptsoption', array(), 'navigations'), array(
                'route' => 'printreceiptsoption'
            ));
        }

        if ($securityContext->isGranted(array(
            new Expression(
                'hasAnyRole("ROLE_ADMIN", "ROLE_GURU", "ROLE_GURU_PIKET")')
        ))) {
            // presence
            $kehadiran = $this->createDropdownMenuItem($menu, $translator->trans('headings.presence', array(), 'navigations'));

            if ($securityContext->isGranted(array(
                new Expression(
                    'hasRole("ROLE_ADMIN")')
            ))) {
                $kehadiran->addChild($translator->trans('links.attendancemachine', array(), 'navigations'), array(
                    'route' => 'attendancemachine'
                ));

                $kehadiran->addChild($translator->trans('links.jadwal.kehadiran', array(), 'navigations'), array(
                    'route' => 'jadwal_kehadiran'
                ));

                $kehadiran->addChild($translator->trans('links.mesin.wakil', array(), 'navigations'), array(
                    'route' => 'mesinproxy'
                ));

                // $kehadiran
                // ->addChild('links.smsbulk',
                // array(
                // 'uri' => '#no'
                // ));
            }

            $kehadiran->addChild($translator->trans('links.studentspresence', array(), 'navigations'), array(
                'route' => 'studentspresence'
            ));
        }

        // heading.student
        // $menu
        // ->addChild('headings.student',
        // array(
        // 'uri' => '#nogo'
        // ));
        // $menu['headings.student']->setAttribute('class', 'dropdown');
        // $menu['headings.student']->setChildrenAttribute('class', 'dropdown-menu');
        // $menu['headings.student']
        // ->setLinkAttributes(
        // array(
        // 'class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'
        // ));
        // $menu['headings.student']
        // ->addChild('info siswa',
        // array(
        // 'uri' => '#nogo'
        // ));

        foreach ($menu as $key => $item) {
            $item->setExtra('routes', array(
                'routes' => $key
            ));
        }

        return $menu;
    }
}
