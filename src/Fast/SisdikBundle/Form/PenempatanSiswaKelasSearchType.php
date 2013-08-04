<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;

class PenempatanSiswaKelasSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        $querybuilder1 = $em->createQueryBuilder()->select('tahun')->from('FastSisdikBundle:Tahun', 'tahun')
                ->where('tahun.sekolah = :sekolah')->orderBy('tahun.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'tahun',
                                'empty_value' => 'label.selectyear', 'required' => false,
                                'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false,
                        ))
                ->add('searchkey', null,
                        array(
                                'attr' => array(
                                    'class' => 'medium search-query', 'placeholder' => 'label.searchkey'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('jenisKelamin', 'choice',
                        array(
                                'required' => false,
                                'choices' => array(
                                    'L' => 'male', 'P' => 'female'
                                ),
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false, 'empty_value' => 'label.gender.empty.select',
                        ))
                ->add('belumDitempatkan', 'checkbox',
                        array(
                                'label' => 'label.belum.ditempatkan', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'searchform';
    }
}
