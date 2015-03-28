<?php

namespace Langgas\SisdikBundle\Form;

use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

class ProfileFormType extends BaseType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var array
     */
    private $definedRoles;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->authorizationChecker = $this->container->get('security.authorization_checker');
        $this->definedRoles = $this->container->getParameter('security.role_hierarchy.roles');
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $otherthanstudents = '';
        foreach ($this->definedRoles as $keys => $values) {
            if ($keys == 'ROLE_SISWA' || $keys == 'ROLE_USER') {
                continue;
            }
            $otherthanstudents .= "'$keys', ";
        }
        $otherthanstudents = preg_replace('/, $/', '', $otherthanstudents);

        if ($this->authorizationChecker->isGranted([new Expression("hasAnyRole($otherthanstudents)")])) {
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
            ->add('nomorPonsel', 'text', [
                'label' => 'label.nomor.ponsel',
                'required' => true,
            ])
        ;

        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            foreach ($this->definedRoles as $keys => $values) {
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
        } elseif ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            foreach ($this->definedRoles as $keys => $values) {
                if (
                    $keys == 'ROLE_SUPER_ADMIN'
                    || $keys == 'ROLE_USER'
                    || $keys == 'ROLE_SISWA'
                    || $keys == 'ROLE_WALI_KELAS'
                    || $keys == 'ROLE_PANITIA_PSB'
                    || $keys == 'ROLE_KETUA_PANITIA_PSB'
                ) {
                    continue;
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
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\User',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_profile';
    }
}
