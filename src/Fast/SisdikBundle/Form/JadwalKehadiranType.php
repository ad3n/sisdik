<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

class JadwalKehadiranType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ));

        $querybuilder1 = $em->createQueryBuilder()->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium selectyear'
                                )
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('kelas')->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')->where('kelas.sekolah = :sekolah')
                ->orderBy('tingkat.urutan', 'ASC')->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'large selectclass'
                                )
                        ));

        $builder
                ->add('statusKehadiran', 'choice',
                        array(
                                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                                'label' => 'label.status.kehadiran', 'multiple' => false,
                                'expanded' => false, 'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('perulangan', 'choice',
                        array(
                                'choices' => JadwalKehadiran::getDaftarPerulangan(),
                                'label' => 'label.perulangan', 'multiple' => false, 'expanded' => false,
                                'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ))
                ->add('mingguanHariKe', 'choice',
                        array(
                                'label' => 'label.day', 'choices' => JadwalKehadiran::getNamaHari(),
                                'multiple' => false, 'expanded' => false, 'required' => false,
                                'empty_value' => 'label.selectweekday',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('bulananHariKe', 'choice',
                        array(
                                'label' => 'label.monthday',
                                'choices' => JadwalKehadiran::getAngkaHariSebulan(), 'multiple' => false,
                                'expanded' => false, 'required' => false,
                                'empty_value' => 'label.selectmonthday',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('paramstatusDariJam', 'time',
                        array(
                                'label' => 'label.paramstatusfrom', 'required' => true, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('paramstatusHinggaJam', 'time',
                        array(
                                'label' => 'label.paramstatusto', 'required' => true, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('kirimSms', 'checkbox',
                        array(
                                'label' => 'label.kirim.sms', 'required' => false, 'label_render' => true,
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('smsJam', 'time',
                        array(
                                'label' => 'label.kirim.sms.jam', 'required' => false, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ));

        $querybuilder4 = $em->createQueryBuilder()->select('template')
                ->from('FastSisdikBundle:Templatesms', 'template')->where('template.sekolah = :sekolah')
                ->orderBy('template.nama', 'ASC')->setParameter('sekolah', $sekolah);

        $builder
                ->add('templatesms', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Templatesms',
                                'label' => 'label.sms.template.entry', 'multiple' => false,
                                'expanded' => false, 'required' => true, 'property' => 'optionLabel',
                                'query_builder' => $querybuilder4,
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));

        $builder
                ->add('otomatisTerhubungMesin', 'checkbox',
                        array(
                                'label' => 'label.otomatis.terhubung.mesin.kehadiran', 'required' => false,
                                'label_render' => true, 'widget_checkbox_label' => 'widget',
                        ))
                ->add('permulaan', 'checkbox',
                        array(
                                'label' => 'label.awal.kehadiran', 'required' => false,
                                'help_block' => 'help.awal.kehadiran',
                                'label_render' => true, 'widget_checkbox_label' => 'widget',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\JadwalKehadiran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_jadwalkehadirantype';
    }
}
