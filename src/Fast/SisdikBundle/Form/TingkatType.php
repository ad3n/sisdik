<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TingkatType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('kode', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ))
                ->add('nama', null,
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('urutan', 'choice',
                        array(
                                'choices' => $this->buildOrderChoices(), 'required' => true,
                                'multiple' => false, 'expanded' => false,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Tingkat'
                        ));
    }

    public function buildOrderChoices() {
        return array_combine(range(1, 10), range(1, 10));
    }

    public function getName() {
        return 'fast_sisdikbundle_tingkattype';
    }
}
