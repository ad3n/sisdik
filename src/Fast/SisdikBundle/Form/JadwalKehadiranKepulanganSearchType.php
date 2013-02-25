<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JadwalKehadiranKepulanganSearchType extends AbstractType
{
    private $container;
    private $sekolah;
    private $repetition = 'harian';

    public function __construct(ContainerInterface $container, $sekolah, $repetition = 'harian') {
        $this->container = $container;
        $this->sekolah = $sekolah;
        $this->repetition = $repetition;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();
        
        $querybuilder1 = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:Tahun', 't')->where('t.sekolah = :sekolah')
                ->orderBy('t.urutan', 'DESC')->setParameter('sekolah', $this->sekolah);
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium selectyear'
                                ), 'label_render' => false,
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.jenjang', 't2')
                ->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('sekolah', $this->sekolah);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas',
                                'label' => 'label.class.entry', 'multiple' => false,
                                'expanded' => false, 'property' => 'nama', 'required' => true,
                                'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium selectclass'
                                ), 'label_render' => false
                        ));

        $builder
                ->add('perulangan', 'choice',
                        array(
                                'choices' => array(
                                        'harian' => 'harian', 'mingguan' => 'mingguan',
                                        'bulanan' => 'bulanan'
                                ), 'label' => 'label.selectrepetition', 'multiple' => false,
                                'expanded' => false, 'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false
                        ));

        if ($this->repetition == 'harian') {
            // no additional select box
        } else if ($this->repetition == 'mingguan') {
            // display additional select box showing day names
            $builder
                    ->add('mingguanHariKe', 'choice',
                            array(
                                    'choices' => $this->buildDayNames(), 'multiple' => false,
                                    'expanded' => false, 'required' => true,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), /* 'empty_value' => 'label.selectweekday', */
                                    'label_render' => false
                            ));
        } else if ($this->repetition == 'bulanan') {
            // display additional select box showing day in a month
            $builder
                    ->add('bulananHariKe', 'choice',
                            array(
                                    'choices' => $this->buildDays(), 'multiple' => false,
                                    'expanded' => false, 'required' => true,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), /* 'empty_value' => 'label.selectmonthday', */
                                    'label_render' => false
                            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        // return '';
        // return 'fast_sisdikbundle_jadwalkehadirankepulangansearchtype';
        return 'searchform';
    }

    public function buildDayNames() {
        return array(
                0 => 'label.sunday', 'label.monday', 'label.tuesday', 'label.wednesday',
                'label.thursday', 'label.friday', 'label.saturday',
        );
    }

    public function buildDays() {
        return array_combine(range(1, 31), range(1, 31));
    }
}
