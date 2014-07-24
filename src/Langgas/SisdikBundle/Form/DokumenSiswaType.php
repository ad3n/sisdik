<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Form\EventListener\DokumenFieldSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class DokumenSiswaType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('jenisDokumenSiswa', new EntityHiddenType($em), [
                'class' => 'LanggasSisdikBundle:JenisDokumenSiswa',
                'label_render' => false,
            ])
            ->add('siswa', new EntityHiddenType($em), [
                'class' => 'LanggasSisdikBundle:Siswa',
                'label_render' => false,
            ])
        ;

        $builder->addEventSubscriber(new DokumenFieldSubscriber($em));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Langgas\SisdikBundle\Entity\DokumenSiswa',
        ]);
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_dokumensiswatype';
    }
}
