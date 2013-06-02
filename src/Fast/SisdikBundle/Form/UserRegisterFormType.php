<?php
namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UserRegisterFormType extends BaseType
{
    private $container;
    private $registrationtype;

    public function __construct(ContainerInterface $container, $registrationtype = 1) {
        $this->container = $container;
        $this->registrationtype = $registrationtype;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('username', null,
                        array(
                                'required' => true, 'label' => 'label.username',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('email', 'email',
                        array(
                                'required' => true, 'label' => 'label.email',
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('plainPassword', 'repeated',
                        array(
                                'type' => 'password', 'invalid_message' => 'fos_user.password.notequal',
                                'first_options' => array(
                                        'label' => 'label.password',
                                        'attr' => array(
                                            'class' => 'medium'
                                        )
                                ),
                                'second_options' => array(
                                        'label' => 'label.repassword',
                                        'attr' => array(
                                            'class' => 'medium'
                                        )
                                ),
                        ));

        $builder
                ->add('name', null,
                        array(
                                'required' => true, 'label' => 'label.name.full',
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));

        foreach ($this->container->getParameter('security.role_hierarchy.roles') as $keys => $values) {
            if ($this->registrationtype == 1) {
                // registration type 1, no school, only for super admin
                if (!($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN')) {
                    continue;
                }
            } else {
                // registration type other than 1, with school
                if ($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN' || $keys == 'ROLE_SISWA'
                        || $keys == 'ROLE_PANITIA_PSB' || $keys == 'ROLE_KETUA_PANITIA_PSB') {
                    continue;
                }
            }

            $roles[$keys] = str_replace('_', ' ', $keys);
        }
        $builder
                ->add('roles', 'choice',
                        array(
                                'choices' => $roles, 'label' => 'label.roles', 'multiple' => true,
                                'expanded' => true,
                        ));

        if ($this->registrationtype != 1) {
            if (($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))) {
                $builder
                        ->add('sekolah', 'entity',
                                array(
                                        'class' => 'FastSisdikBundle:Sekolah', 'label' => 'label.school',
                                        'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                        'required' => true,
                                ));
            } else {
                $user = $this->container->get('security.context')->getToken()->getUser();
                $sekolah = $user->getSekolah();

                if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                    $em = $this->container->get('doctrine')->getManager();
                    $querybuilder = $em->createQueryBuilder()->select('t')
                            ->from('FastSisdikBundle:Sekolah', 't')->where('t.id = :sekolah')
                            ->setParameter('sekolah', $sekolah);
                    $builder
                            ->add('sekolah', 'entity',
                                    array(
                                            'class' => 'FastSisdikBundle:Sekolah', 'label' => 'label.school',
                                            'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                            'empty_value' => false, 'required' => true,
                                            'query_builder' => $querybuilder,
                                            'attr' => array(
                                                'class' => 'large'
                                            )
                                    ));
                }
            }
        }

        $builder
                ->add('enabled', 'checkbox',
                        array(
                                'label' => 'label.enabled', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                                'data_class' => 'Fast\SisdikBundle\Entity\User',
                                'validation_groups' => array(
                                    'Registration'
                                ),
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_userregister';
    }

}
