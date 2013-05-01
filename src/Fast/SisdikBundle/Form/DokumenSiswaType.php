<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\DokumenFieldSubscriber;
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

        $builder->addEventSubscriber(new DokumenFieldSubscriber($em));
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
