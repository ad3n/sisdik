<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\BiayaPendaftaranSubscriber;
use Fast\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DaftarBiayaPendaftaranType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('biayaPendaftaran', new EntityHiddenType($em),
                        array(
                            'required' => true, 'class' => 'FastSisdikBundle:BiayaPendaftaran',
                        ))
                ->add('nama', 'hidden',
                        array(
                            'required' => false,
                        ))
                ->add('nominal', 'hidden',
                        array(
                            'required' => true,
                        ));

        $builder->addEventSubscriber(new BiayaPendaftaranSubscriber($em));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\DaftarBiayaPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_daftarbiayapendaftarantype';
    }
}
