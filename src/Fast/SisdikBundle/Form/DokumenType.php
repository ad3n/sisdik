<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\DokumenFieldSubscriber;
use Fast\SisdikBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DokumenType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('jenisDokumenSiswa', new EntityHiddenType($em),
                        array(
                            'class' => 'FastSisdikBundle:JenisDokumenSiswa', 'label_render' => false,
                        ))
                ->add('siswa', new EntityHiddenType($em),
                        array(
                            'class' => 'FastSisdikBundle:Siswa', 'label_render' => false,
                        ));

        $builder->addEventSubscriber(new DokumenFieldSubscriber());
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Dokumen'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_dokumentype';
    }

}
