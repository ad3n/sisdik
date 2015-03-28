<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\SekolahSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class UserFormType extends AbstractType
{
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @InjectParams({
     *     "authorizationChecker" = @Inject("security.authorization_checker"),
     *     "tokenStorage" = @Inject("security.token_storage")
     * })
     *
     * @param AuthorizationChecker  $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(AuthorizationChecker $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                'required' => true,
                'read_only' => true,
                'attr' => [
                    'class' => 'disabled large',
                ],
            ])
            ->add('email', 'email', [
                'required' => true,
                'read_only' => true,
                'attr' => [
                    'class' => 'disabled xlarge',
                ],
            ])
            ->add('name', null, [
                'required' => true,
                'label' => 'label.name.full',
                'read_only' => true,
                'attr' => [
                    'class' => 'disabled xlarge',
                ],
            ])
        ;

        if ($options['mode'] == 1) {
            // role user and super admin, not registered to any school
            foreach ($options['role_hierarchy'] as $keys => $values) {
                if (!($keys == 'ROLE_USER' || $keys == 'ROLE_SUPER_ADMIN')) {
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
        } elseif ($options['mode'] == 2) {
            // role siswa, nothing
        } elseif ($options['mode'] == 3) {
            foreach ($options['role_hierarchy'] as $keys => $values) {
                if ($keys == 'ROLE_USER'
                        || $keys == 'ROLE_SISWA'
                        || $keys == 'ROLE_SUPER_ADMIN'
                        || $keys == 'ROLE_WALI_KELAS'
                        || $keys == 'ROLE_PANITIA_PSB'
                        || $keys == 'ROLE_KETUA_PANITIA_PSB') {
                    continue;
                }
                $string = str_replace('ROLE_', ' ', $keys);
                $roles[$keys] = str_replace('_', ' ', $string);
            }

            $builder
                ->add('nomorPonsel', 'text', [
                    'required' => false,
                    'label' => 'label.nomor.ponsel',
                ])
                ->add('roles', 'choice', [
                    'choices' => $roles,
                    'label' => 'label.roles',
                    'multiple' => true,
                    'expanded' => true,
                ])
            ;
        }

        if ($options['mode'] > 1) {
            if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
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

                $builder->addEventSubscriber(new SekolahSubscriber($sekolah));

                $builder
                    ->add('sekolah', 'sisdik_entityhidden', [
                        'required' => true,
                        'class' => 'LanggasSisdikBundle:Sekolah',
                        'data' => $sekolah->getId(),
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
                'mode' => 0,
                'role_hierarchy' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_useredit';
    }
}
