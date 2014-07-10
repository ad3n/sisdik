<?php
namespace Langgas\SisdikBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                'required' => true,
                'label' => 'label.username',
            ])
            ->add('email', 'email', [
                'required' => true,
                'label' => 'label.email',
                'attr' => array(
                    'class' => 'medium',
                ),
            ])
            ->add('plainPassword', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'fos_user.password.notequal',
                'options' => [
                    'label' => 'label.password',
                ],
            ])
            ->add('name', null, [
                'required' => true,
                'label' => 'label.name.full',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
        ;

        $builder->get('plainPassword')->get('second')->setAttribute('label', 'label.repassword');
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_registration';
    }
}
