<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PenjurusanType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('kode', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('kepala', null,
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));

        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {

            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Penjurusan', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.sekolah ASC, t.root, t.lft', 'ASC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('parent', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Penjurusan',
                                    'label' => 'label.parentnode', 'multiple' => false,
                                    'expanded' => false, 'property' => 'optionLabel',
                                    'empty_value' => 'label.select.parentnode',
                                    'required' => false, 'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'xlarge'
                                    )
                            ));

            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Sekolah', 't')->where('t. = :sekolah')
                    ->setParameter('sekolah', $sekolah);
            $builder
                    ->add('sekolah', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Sekolah',
                                    'label' => 'label.school', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'large'
                                    )
                            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Penjurusan'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_penjurusantype';
    }
}
