<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImbalanPendaftaranType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahunmasuk', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.tahun', 'DESC')
                    ->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahunmasuk', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahunmasuk',
                                    'label' => 'label.yearentry.entry',
                                    'multiple' => false, 'expanded' => false,
                                    'property' => 'tahun', 'empty_value' => false,
                                    'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'small'
                                    )
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Gelombang', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah);
            $builder
                    ->add('gelombang', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Gelombang',
                                    'label' => 'label.admissiongroup.entry',
                                    'multiple' => false, 'expanded' => false,
                                    'property' => 'nama', 'empty_value' => false,
                                    'required' => true,
                                    'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'large'
                                    )
                            ));

            $querybuilder3 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:JenisImbalan', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                    ->setParameter('sekolah', $sekolah);
            $builder
                    ->add('jenisimbalan', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:JenisImbalan',
                                    'label' => 'label.reward.type.name',
                                    'multiple' => false, 'expanded' => false,
                                    'property' => 'nama', 'empty_value' => false,
                                    'required' => true,
                                    'query_builder' => $querybuilder3,
                                    'attr' => array(
                                        'class' => 'medium'
                                    )
                            ));
        }

        $builder
                ->add('nominal', 'money',
                        array(
                                'currency' => 'IDR', 'required' => true,
                                'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\ImbalanPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_imbalanpendaftarantype';
    }
}
