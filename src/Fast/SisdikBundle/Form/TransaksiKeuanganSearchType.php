<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TransaksiKeuanganSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('dariTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.dari.tanggal'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('hinggaTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.hingga.tanggal.singkat'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('searchkey', null,
                        array(
                                'attr' => array(
                                    'class' => 'medium search-query', 'placeholder' => 'label.searchkey'
                                ), 'required' => false, 'label_render' => false,
                        ))
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
                ->add('jumlahBayar', 'number',
                        array(
                                'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'small', 'placeholder' => 'label.jumlah.bayar'
                                ), 'label_render' => false, 'required' => false, 'error_bubbling' => true,
                                'invalid_message' => $this->container->get('translator')
                                        ->trans('pencarian.nominal.tidak.sah', array(), 'validators'),
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
