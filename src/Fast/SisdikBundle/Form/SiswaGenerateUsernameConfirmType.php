<?php

namespace Fast\SisdikBundle\Form;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaGenerateUsernameConfirmType extends AbstractType
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
                ->add('confirmlist', 'checkbox',
                        array(
                                'label' => 'label.generated.valid', 'required' => true,
                                'help_block' => 'help.confirm.generated.username',
                                'widget_checkbox_label' => 'widget',
                                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                        ))
                ->add('captcha', 'captcha',
                        array(
                                'attr' => array(
                                        'class' => 'medium', 'placeholder' => 'help.type.captcha',
                                        'autocomplete' => 'off'
                                ), 'as_url' => true, 'reload' => true,
                                'help_block' => 'help.captcha.username.explain',
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswagenerateusernameconfirmtype';
    }
}

