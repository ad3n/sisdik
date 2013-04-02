<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

class JadwalKehadiranKepulanganType extends AbstractType
{
    private $container;
    private $sekolah;

    public function __construct(ContainerInterface $container, $sekolah) {
        $this->container = $container;
        $this->sekolah = $sekolah;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();
        $securityContext = $this->container->get('security.context');

        $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:TahunAkademik', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.urutan', 'DESC')
                ->setParameter('sekolah', $this->sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.jenjang', 't2')->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('sekolah', $this->sekolah);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ));

        $builder
                ->add('perulangan', 'choice',
                        array(
                                'choices' => array(
                                    'harian' => 'harian', 'mingguan' => 'mingguan', 'bulanan' => 'bulanan'
                                ), 'label' => 'label.repetition', 'multiple' => false, 'expanded' => false,
                                'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ));

        $builder
                ->add('mingguanHariKe', 'choice',
                        array(
                                'label' => 'label.day', 'choices' => $this->buildDayNames(),
                                'multiple' => false, 'expanded' => false, 'required' => false,
                                'empty_value' => 'label.selectweekday',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('bulananHariKe', 'choice',
                        array(
                                'label' => 'label.monthday', 'choices' => $this->buildDays(),
                                'multiple' => false, 'expanded' => false, 'required' => false,
                                'empty_value' => 'label.selectmonthday',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));

        $querybuilder3 = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')->where('t.sekolah = :sekolah')
                ->orderBy('t.nama', 'ASC')->setParameter('sekolah', $this->sekolah);
        $builder
                ->add('statusKehadiranKepulangan', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:StatusKehadiranKepulangan',
                                'label' => 'label.presence.status.entry', 'multiple' => false,
                                'expanded' => false, 'property' => 'nama', 'required' => true,
                                'query_builder' => $querybuilder3,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));

        $builder
                ->add('paramstatusDariJam', 'time',
                        array(
                                'label' => 'label.paramstatusfrom', 'required' => false, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('paramstatusHinggaJam', 'time',
                        array(
                                'label' => 'label.paramstatusto', 'required' => false, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('smsRealtimeDariJam', 'time',
                        array(
                                'label' => 'label.realtimesms.hourfrom', 'required' => false,
                                'input' => 'string', 'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('smsRealtimeHinggaJam', 'time',
                        array(
                                'label' => 'label.realtimesms.hourto', 'required' => false,
                                'input' => 'string', 'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('smsMassalJam', 'time',
                        array(
                                'label' => 'label.massivesms.hour', 'required' => false, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('dariJam', 'time',
                        array(
                                'label' => 'label.presencefrom', 'required' => false, 'input' => 'string',
                                'widget' => 'single_text', 'with_seconds' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ));
        //                 ->add('hinggaJam', 'time',
        //                         array(
        //                                 'label' => 'label.presenceto', 'required' => false,
        //                                 'input' => 'string', 'widget' => 'single_text',
        //                                 'with_seconds' => false,
        //                                 'attr' => array(
        //                                     'class' => 'mini'
        //                                 )
        //                         ))

        $builder
                ->add('kirimSmsRealtime', 'checkbox',
                        array(
                                'label' => 'label.sendsms.realtime', 'required' => false,
                                'label_render' => true, 'widget_checkbox_label' => 'widget',
                        ))
                ->add('commandRealtime', null,
                        array(
                                'label' => 'label.command.realtime',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('kirimSmsMassal', 'checkbox',
                        array(
                                'label' => 'label.sendsms.massive', 'required' => false,
                                'label_render' => true, 'widget_checkbox_label' => 'widget',
                        ))
                ->add('commandMassal', null,
                        array(
                                'label' => 'label.command.massive',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('commandJadwal', null,
                        array(
                                'label' => 'label.presencecommand',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ));

        $querybuilder4 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Templatesms', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $this->sekolah);

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
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\JadwalKehadiranKepulangan'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_jadwalkehadirankepulangantype';
    }

    public function buildDayNames() {
        return array(
                0 => 'label.sunday', 'label.monday', 'label.tuesday', 'label.wednesday', 'label.thursday',
                'label.friday', 'label.saturday',
        );
    }

    public function buildDays() {
        return array_combine(range(1, 31), range(1, 31));
    }
}
