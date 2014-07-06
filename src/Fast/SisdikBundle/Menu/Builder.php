<?php
namespace Fast\SisdikBundle\Menu;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\FactoryInterface;
use Knp\Menu\Renderer\ListRenderer;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

/**
 * @author Ihsan Faisal
 */
class Builder extends ContainerAware
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param FactoryInterface $factory
     * @param ContainerInterface $container
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $container
    )
    {
        $this->factory = $factory;
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @return ItemInterface
     */
    public function createMainMenu(Request $request)
    {
        $securityContext = $this->container->get('security.context');
        $translator = $this->container->get('translator');

        $menu = $this->factory->createItem('root', [
            'navbar' => true,
        ]);
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        if ($securityContext->isGranted([
            new Expression('hasRole("ROLE_SUPER_ADMIN")')
        ])) {
            $supersettings = $menu->addChild(
                $translator->trans('headings.pengaturan.sisdik', [], 'navigations'),
                [
                    'dropdown' => true,
                ]
            );

            $supersettings->addChild($translator->trans('links.alluser', [], 'navigations'), ['route' => 'settings_user']);
            $supersettings->addChild($translator->trans('links.schools', [], 'navigations'), ['route' => 'settings_school_list']);
            $supersettings->addChild($translator->trans('links.layanansms', [], 'navigations'), ['route' => 'layanansms']);
            $supersettings->addChild($translator->trans('links.token.sekolah', [], 'navigations'), ['route' => 'token-sekolah']);
        }

        if ($securityContext->isGranted([
            new Expression('hasRole("ROLE_SISWA") and not hasAnyRole("ROLE_SUPER_ADMIN", "ROLE_WALI_KELAS")')
        ])) {
            $menu->addChild($translator->trans('links.kehadiran.siswa', [], 'navigations'), ['route' => '_index']);
        }

        if ($securityContext->isGranted([
            new Expression('hasRole("ROLE_ADMIN")')
        ])) {
            $settings = $menu->addChild(
                $translator->trans('headings.setting', [], 'navigations'),
                [
                    'dropdown' => true,
                ]
            );

            $settings->addChild($translator->trans('links.user', [], 'navigations'), ['route' => 'settings_user_inschool_list']);
            $settings->addChild($translator->trans('links.school', [], 'navigations'), ['route' => 'settings_specsch']);
            $settings->addChild($translator->trans('links.placement', [], 'navigations'), ['route' => 'settings_placement']);
            $settings->addChild($translator->trans('links.year', [], 'navigations'), ['route' => 'settings_year']);
            $settings->addChild($translator->trans('links.admissiongroup', [], 'navigations'), ['route' => 'settings_admissiongroup']);
            $settings->addChild($translator->trans('links.academicyear', [], 'navigations'), ['route' => 'academicyear']);
            $settings->addChild($translator->trans('links.smstemplate', [], 'navigations'), ['route' => 'sms_template']);
            $settings->addChild($translator->trans('links.smspendaftaran', [], 'navigations'), ['route' => 'smspendaftaran']);
            $settings->addChild($translator->trans('links.jenisdokumensiswa', [], 'navigations'), ['route' => 'jenisdokumensiswa']);
        }

        if ($securityContext->isGranted([
            new Expression('hasAnyRole("ROLE_BENDAHARA")')
        ])) {
            $fees = $menu->addChild($translator->trans('headings.fee', [], 'navigations'), [
                'dropdown' => true,
            ]);

            $fees->addChild($translator->trans('links.fee.type', [], 'navigations'), ['route' => 'fee_type']);
            $fees->addChild($translator->trans('links.fee.registration', [], 'navigations'), ['route' => 'fee_registration']);
            $fees->addChild($translator->trans('links.fee.once', [], 'navigations'), ['route' => 'fee_once']);
            $fees->addChild($translator->trans('links.fee.recur', [], 'navigations'), ['route' => 'fee_recur']);
            $fees->addChild($translator->trans('links.reward.type', [], 'navigations'), ['route' => 'rewardtype']);
            $fees->addChild($translator->trans('links.reward.amount', [], 'navigations'), ['route' => 'rewardamount']);
        }

        $rolependaftaran = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_PANITIA_PSB", "ROLE_KETUA_PANITIA_PSB")';
        if ($securityContext->isGranted([
            new Expression($rolependaftaran)
        ])) {
            $pendaftaran = $menu->addChild($translator->trans('headings.pendaftaran', [], 'navigations'), [
                'dropdown' => true,
            ]);

            if ($securityContext->isGranted([
                new Expression("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
            ])) {
                $pendaftaran->addChild($translator->trans('links.regcommittee', [], 'navigations'), ['route' => 'regcommittee']);
            }

            $pendaftaran->addChild($translator->trans('links.registration', [], 'navigations'), ['route' => 'applicant']);
            $pendaftaran->addChild($translator->trans('links.laporan.pendaftaran', [], 'navigations'), ['route' => 'laporan-pendaftaran']);
            $pendaftaran->addChild($translator->trans('links.sekolahasal', [], 'navigations'), ['route' => 'sekolahasal']);
            $pendaftaran->addChild($translator->trans('links.referensi', [], 'navigations'), ['route' => 'referensi']);

            if ($securityContext->isGranted([
                new Expression('hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH", "ROLE_KETUA_PANITIA_PSB")')
            ])) {
                $pendaftaran->addChild($translator->trans('links.tahkik', [], 'navigations'), ['route' => 'tahkik']);
            }
        }

        $roleakademik = 'hasAnyRole("ROLE_ADMIN", "ROLE_KEPALA_SEKOLAH", "ROLE_WAKIL_KEPALA_SEKOLAH")';
        if ($securityContext->isGranted([
            new Expression($roleakademik)]
        )) {
            $academic = $menu->addChild($translator->trans('headings.academic', [], 'navigations'), [
                'dropdown' => true,
            ]);

            $academic->addChild($translator->trans('links.data.academiccalendar', [], 'navigations'), ['route' => 'kalender-akademik']);
            $academic->addChild($translator->trans('links.tingkat', [], 'navigations'), ['route' => 'tingkat-kelas']);
            $academic->addChild($translator->trans('links.data.class', [], 'navigations'), ['route' => 'data_class']);
            $academic->addChild($translator->trans('links.data.classguardian', [], 'navigations'), ['route' => 'data_classguardian']);
            $academic->addChild($translator->trans('links.siswa', [], 'navigations'), ['route' => 'siswa']);
            $academic->addChild($translator->trans('links.penempatan.siswa.kelas', [], 'navigations'), ['route' => 'penempatan-siswa-kelas']);
        }

        if ($securityContext->isGranted([
            new Expression('hasAnyRole("ROLE_BENDAHARA", "ROLE_KASIR")')
        ])) {
            $payments = $menu->addChild($translator->trans('headings.payments', [], 'navigations'), [
                'dropdown' => true,
            ]);

            $payments->addChild($translator->trans('links.applicant.payment', [], 'navigations'), ['route' => 'applicant_payment']);

            if ($securityContext->isGranted([
                new Expression("hasAnyRole('ROLE_BENDAHARA')")
            ])) {
                $payments->addChild($translator->trans('links.laporan.transaksi.keuangan', [], 'navigations'), ['route' => 'laporan-transaksi-keuangan']);
            }

            $payments->addChild($translator->trans('links.laporan.pembayaran.pendaftaran', [], 'navigations'), ['route' => 'laporan-pembayaran-pendaftaran']);
            $payments->addChild($translator->trans('links.printreceiptsoption', [], 'navigations'), ['route' => 'printreceiptsoption']);
        }

        if ($securityContext->isGranted([
            new Expression('hasAnyRole("ROLE_ADMIN", "ROLE_GURU", "ROLE_GURU_PIKET")')
        ])) {
            $kehadiran = $menu->addChild($translator->trans('headings.presence', [], 'navigations'), [
                'dropdown' => true,
            ]);

            if ($securityContext->isGranted([
                new Expression('hasRole("ROLE_ADMIN")')
            ])) {
                $kehadiran->addChild($translator->trans('links.attendancemachine', [], 'navigations'), ['route' => 'attendancemachine']);
                $kehadiran->addChild($translator->trans('links.jadwal.kehadiran', [], 'navigations'), ['route' => 'jadwal_kehadiran']);
                /* $kehadiran->addChild($translator->trans('links.mesin.wakil', [], 'navigations'), ['route' => 'mesinproxy']); */
            }

            $kehadiran->addChild($translator->trans('links.kehadiran.siswa', [], 'navigations'), ['route' => 'kehadiran-siswa']);
            $kehadiran->addChild($translator->trans('links.laporan.kehadiran.siswa', [], 'navigations'), ['route' => 'laporan-kehadiran-siswa']);
        }

        foreach ($menu as $key => $item) {
            $item->setExtra('routes', [
                'routes' => $key
            ]);
        }

        return $menu;
    }
}
