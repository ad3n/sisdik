<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfirmationType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $data;

    /**
     * @param ContainerInterface $container
     * @param string             $data
     */
    public function __construct(ContainerInterface $container, $data)
    {
        $this->container = $container;
        $this->data = $data;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sessiondata', 'hidden', [
                'data' => $this->data,
            ])
            ->add('confirm', 'checkbox', [
                'label' => 'label.saya.mengerti',
                'required' => true,
                'help_block' => 'help.konfirmasi.saya.mengerti',
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
            ->add('captcha', 'captcha', [
                'attr' => [
                    'class' => 'medium',
                    'placeholder' => 'help.type.captcha',
                    'autocomplete' => 'off',
                ],
                'as_url' => true,
                'reload' => true,
                'help_block' => 'help.captcha.penjelasan.ubah.hapus',
                'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_confirmtype';
    }
}
