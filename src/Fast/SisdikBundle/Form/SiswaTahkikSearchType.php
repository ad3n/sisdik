<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;

class SiswaTahkikSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()->select('gelombang')
                ->from('FastSisdikBundle:Gelombang', 'gelombang')->where('gelombang.sekolah = :sekolah')
                ->orderBy('gelombang.urutan', 'ASC')->setParameter('sekolah', $sekolah->getId());
        $builder
                ->add('gelombang', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Gelombang', 'multiple' => false,
                                'expanded' => false, 'property' => 'nama',
                                'empty_value' => 'label.selectadmissiongroup', 'required' => false,
                                'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false,
                        ));

        $builder
                ->add('pembandingBayar', 'choice',
                        array(
                                'required' => true,
                                'choices' => array(
                                    '=' => '=', '>' => '>', '<' => '<', '>=' => '≥', '<=' => '≤'
                                ),
                                'attr' => array(
                                    'class' => 'mini pembanding-bayar'
                                ), 'label_render' => false
                        ))
                ->add('jumlahBayar', 'text',
                        array(
                                'label' => 'label.searchkey', 'required' => false,
                                'attr' => array(
                                    'class' => 'small', 'placeholder' => 'Jumlah Bayar'
                                ), 'label_render' => false,
                        ))
                ->add('persenBayar', 'checkbox',
                        array(
                            'required' => false, 'label_render' => false,
                        ))
                ->add('searchkey', 'text',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'medium search-query', 'placeholder' => 'label.searchkey'
                                ), 'label_render' => false,
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
                ->add('tertahkik', 'checkbox',
                        array(
                                'label' => 'label.tertahkik', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('kelengkapanDokumen', 'text',
                        array(
                                'required' => false, 'label_render' => false,
                                'attr' => array(
                                    'class' => 'mini kelengkapan-dokumen'
                                ),
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
