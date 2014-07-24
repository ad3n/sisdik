<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @FormType
 */
class ConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sessiondata', 'hidden', [
                'data' => $options['sessiondata'],
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'sessiondata' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_confirm';
    }
}
