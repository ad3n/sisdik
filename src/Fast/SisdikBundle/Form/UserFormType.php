<?php
namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * UserFormType will be used both for ADMIN and SUPER ADMIN role,
 * so we need to put certain condition depend on the logged in user session
 *
 * @author Ihsan Faisal
 *
 */
class UserFormType extends AbstractType
{
    private $container;
    private $formoption;

    public function __construct(ContainerInterface $container, $formoption = 0) {
        $this->container = $container;
        $this->formoption = $formoption;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('username', null,
                        array(
                                'required' => true, 'read_only' => true,
                                'attr' => array(
                                    'class' => 'disabled large'
                                )
                        ))
                ->add('email', 'email',
                        array(
                                'required' => true, 'read_only' => true,
                                'attr' => array(
                                    'class' => 'disabled xlarge'
                                )
                        ))
                ->add('name', null,
                        array(
                                'required' => true, 'label' => 'label.name.full', 'read_only' => true,
                                'attr' => array(
                                    'class' => 'disabled xlarge'
                                )
                        ));

        if ($this->formoption == 1) {
            // role user and super admin, not registered to any school
            foreach ($this->container->getParameter('security.role_hierarchy.roles') as $keys => $values) {
                if (!($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN')) {
                    continue;
                }
                $roles[$keys] = str_replace('_', ' ', $keys);
            }

            $builder
                    ->add('roles', 'choice',
                            array(
                                    'choices' => $roles, 'label' => 'label.roles', 'multiple' => true,
                                    'expanded' => true,
                            ));
        } else if ($this->formoption == 2) {
            // role siswa, nothing
        } else if ($this->formoption == 3) {
            foreach ($this->container->getParameter('security.role_hierarchy.roles') as $keys => $values) {
                if ($keys == 'ROLE_USER' || $keys == 'ROLE_SISWA' || $keys == 'ROLE_SUPER_ADMIN'
                        || $keys == 'ROLE_PANITIA_PSB' || $keys == 'ROLE_KETUA_PANITIA_PSB') {
                    continue;
                }
                $roles[$keys] = str_replace('_', ' ', $keys);
            }

            $builder
                    ->add('roles', 'choice',
                            array(
                                    'choices' => $roles, 'label' => 'label.roles', 'multiple' => true,
                                    'expanded' => true,
                            ));
        }

        if ($this->formoption > 1) {
            if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
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
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_user';
    }
}
