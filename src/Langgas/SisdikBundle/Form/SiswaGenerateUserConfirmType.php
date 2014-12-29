<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @FormType
 */
class SiswaGenerateUserConfirmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sessiondata', 'hidden', [
                'data' => $options['sessiondata'],
            ])
            ->add('confirmlist', 'checkbox', [
                'label' => 'label.generated.valid',
                'required' => true,
                'help_block' => 'help.confirm.generated.username',
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
                'help_block' => 'help.captcha.username.explain',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'sessiondata' => '',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_konfirmusersiswa';
    }
}
