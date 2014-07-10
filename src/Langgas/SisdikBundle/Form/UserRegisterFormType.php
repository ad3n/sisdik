<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class UserRegisterFormType extends BaseType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var integer
     */
    private $registrationtype;

    /**
     * @param ContainerInterface $container
     * @param number             $registrationtype
     */
    public function __construct(ContainerInterface $container, $registrationtype = 1)
    {
        $this->container = $container;
        $this->registrationtype = $registrationtype;
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
        ;

        $builder
            ->add('name', null, [
                'required' => true,
                'label' => 'label.name.full',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;

        foreach ($this->container->getParameter('security.role_hierarchy.roles') as $keys => $values) {
            if ($this->registrationtype == 1) {
                // registration type 1, no school, only for super admin
                if (!($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN')) {
                    continue;
                }
            } else {
                // registration type other than 1, with school
                if ($keys == 'ROLE_USER'
                        || $keys == 'ROLE_SUPER_ADMIN'
                        || $keys == 'ROLE_SISWA'
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
        ;

        if ($this->registrationtype != 1) {
            if (($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))) {
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
                $user = $this->container->get('security.context')->getToken()->getUser();
                $sekolah = $user->getSekolah();

                if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                    $em = $this->container->get('doctrine')->getManager();

                    $builder
                        ->add('sekolah', new EntityHiddenType($em), [
                            'required' => true,
                            'class' => 'LanggasSisdikBundle:Sekolah',
                            'data' => $sekolah->getId(),
                        ])
                    ;
                }
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
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_userregister';
    }
}
