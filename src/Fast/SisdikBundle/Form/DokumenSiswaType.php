<?php

namespace Fast\SisdikBundle\Form;
use Doctrine\ORM\EntityRepository;
use Fast\SisdikBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DokumenSiswaType extends AbstractType
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
                ->add('dokumenSiswa', 'collection',
                        array(
                                'type' => new DokumenType($this->container), 'required' => true,
                                'allow_add' => true, 'allow_delete' => true, 'by_reference' => true,
                                'prototype' => true,
                                'options' => array(
                                    'widget_control_group' => true, 'label_render' => false,
                                ), 'label_render' => false, 'widget_control_group' => false,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\DokumenSiswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_dokumensiswatype';
    }
}
