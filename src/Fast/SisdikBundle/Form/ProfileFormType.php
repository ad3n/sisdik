<?php
namespace Fast\SisdikBundle\Form;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class ProfileFormType extends BaseType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $securityContext = $this->container->get('security.context');

        $definedRoles = $this->container->getParameter('security.role_hierarchy.roles');

        $otherthanstudents = '';
        foreach ($definedRoles as $keys => $values) {
            if ($keys == 'ROLE_SISWA' || $keys == 'ROLE_USER') {
                continue;
            }
            $otherthanstudents .= "'$keys', ";
        }
        $otherthanstudents = preg_replace('/, $/', '', $otherthanstudents);

        if ($securityContext->isGranted([new Expression("hasAnyRole($otherthanstudents)")])) {
            $builder
                ->add('username', null, [
                    'required' => true,
                ])
            ;
        }

        $builder
            ->add('email', 'email', [
                'required' => true,
            ])
            ->add('name', null, [
                'required' => true,
                'label' => 'label.name.full',
            ])
        ;

        if ($securityContext->isGranted('ROLE_SUPER_ADMIN')) {
            foreach ($definedRoles as $keys => $values) {
                $roles[$keys] = str_replace('_', ' ', $keys);
            }

            $builder
                ->add('roles', 'choice', [
                    'choices' => $roles,
                    'label' => 'label.roles',
                    'multiple' => true,
                    'expanded' => true,
                ])
            ;
        } elseif ($securityContext->isGranted('ROLE_ADMIN')) {
            foreach ($definedRoles as $keys => $values) {
                if (
                    $keys == 'ROLE_SUPER_ADMIN'
                    || $keys == 'ROLE_USER'
                    || $keys == 'ROLE_SISWA'
                    || $keys == 'ROLE_PANITIA_PSB'
                    || $keys == 'ROLE_KETUA_PANITIA_PSB'
                ) {
                    continue;
                }
                $roles[$keys] = str_replace('_', ' ', $keys);
            }

            $builder
                ->add('roles', 'choice', [
                    'choices' => $roles,
                    'label' => 'label.roles',
                    'multiple' => true,
                    'expanded' => true,
                ])
            ;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Fast\SisdikBundle\Entity\User',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_profile';
    }
}
