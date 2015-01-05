<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class UserRegisterFormType extends BaseType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context")
     * })
     *
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                'required' => true,
                'label' => 'label.username',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('email', 'email', [
                'required' => true,
                'label' => 'label.email',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('plainPassword', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'fos_user.password.notequal',
                'first_options' => [
                    'label' => 'label.password',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ],
                'second_options' => [
                    'label' => 'label.repassword',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ],
            ])
            ->add('name', null, [
                'required' => true,
                'label' => 'label.name.full',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;

        foreach ($options['role_hierarchy'] as $keys => $values) {
            if ($options['mode'] == 1) {
                // registration type 1, no school, only for super admin
                if (!($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN')) {
                    continue;
                }
            } else {
                // registration type other than 1, with school
                if ($keys == 'ROLE_USER'
                        || $keys == 'ROLE_SUPER_ADMIN'
                        || $keys == 'ROLE_SISWA'
                        || $keys == 'ROLE_WALI_KELAS'
                        || $keys == 'ROLE_PANITIA_PSB'
                        || $keys == 'ROLE_KETUA_PANITIA_PSB') {
                    continue;
                }
            }

            $string = str_replace('ROLE_', ' ', $keys);
            $roles[$keys] = str_replace('_', ' ', $string);
        }
        $builder
            ->add('roles', 'choice', [
                'choices' => $roles,
                'label' => 'label.roles',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('nomorPonsel', 'text', [
                'required' => false,
                'label' => 'label.nomor.ponsel',
            ])
        ;

        if ($options['mode'] != 1) {
            if (($this->securityContext->isGranted('ROLE_SUPER_ADMIN'))) {
                $builder
                    ->add('sekolah', 'entity', [
                        'class' => 'LanggasSisdikBundle:Sekolah',
                        'label' => 'label.school',
                        'multiple' => false,
                        'expanded' => false,
                        'property' => 'nama',
                        'required' => true,
                    ])
                ;
            } else {
                $sekolah = $this->getSekolah();

                $builder
                    ->add('sekolah', 'sisdik_entityhidden', [
                        'required' => true,
                        'class' => 'LanggasSisdikBundle:Sekolah',
                        'data' => $sekolah->getId(),
                    ])
                ;
            }
        }

        $builder
            ->add('enabled', 'checkbox', [
                'label' => 'label.enabled',
                'required' => false,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\User',
                'validation_groups' => [
                    'Registration',
                ],
                'mode' => 1,
                'role_hierarchy' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_registeruser';
    }
}
