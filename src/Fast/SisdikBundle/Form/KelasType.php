<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KelasType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()->select('tingkat')
                ->from('FastSisdikBundle:Tingkat', 'tingkat')->where('tingkat.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tingkat', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tingkat', 'label' => 'label.tingkat',
                                'multiple' => false, 'expanded' => false, 'property' => 'optionLabel',
                                'empty_value' => false, 'required' => true, 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));

        $querybuilder = $em->createQueryBuilder()->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('tahunAkademik.nama', 'DESC')->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'empty_value' => false, 'required' => true, 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('nama', 'text',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('kode', 'text',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('keterangan', 'textarea',
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'required' => false,
                        ))
                ->add('urutan', 'choice',
                        array(
                                'choices' => $this->buildOrderChoices(), 'required' => true,
                                'multiple' => false, 'expanded' => false,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Kelas'
                        ));
    }

    public function buildOrderChoices() {
        return array_combine(range(1, 30), range(1, 30));
    }

    public function getName() {
        return 'fast_sisdikbundle_kelastype';
    }
}
