<?php
namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('username', null,
                        array(
                            'required' => true, 'label' => 'label.username'
                        ))
                ->add('email', 'email',
                        array(
                                'required' => true, 'label' => 'label.email',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('plainPassword', 'repeated',
                        array(
                                'type' => 'password',
                                'invalid_message' => 'fos_user.password.notequal',
                                'options' => array(
                                    'label' => 'label.password'
                                )
                        ));
        $builder->get('plainPassword')->get('second')->setAttribute('label', 'label.repassword');

        // add custom field
        $builder
                ->add('name', null,
                        array(
                                'required' => true, 'label' => 'label.name.full',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ));
    }

    public function getName() {
        return 'fast_user_registration';
    }
}
