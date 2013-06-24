<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiayaConfirmType extends AbstractType
{
    private $container;
    private $data;

    public function __construct(ContainerInterface $container, $data) {
        $this->container = $container;
        $this->data = $data;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sessiondata', 'hidden',
                        array(
                            'data' => $this->data,
                        ))
                ->add('confirm', 'checkbox',
                        array(
                                'label' => 'label.saya.mengerti', 'required' => true,
                                'help_block' => 'help.konfirmasi.saya.mengerti',
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('captcha', 'captcha',
                        array(
                                'attr' => array(
                                        'class' => 'medium', 'placeholder' => 'help.type.captcha',
                                        'autocomplete' => 'off'
                                ), 'as_url' => true, 'reload' => true,
                                'help_block' => 'help.captcha.penjelasan.ubah.biaya',
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_biayaconfirmtype';
    }
}
