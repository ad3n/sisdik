<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Langgas\SisdikBundle\Form\EventListener\BiayaPendaftaranSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class DaftarBiayaPendaftaranType extends AbstractType
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
            ->add('biayaPendaftaran', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:BiayaPendaftaran',
            ])
            ->add('nama', 'hidden', [
                'required' => false,
            ])
            ->add('nominal', 'hidden', [
                'required' => true,
            ])
        ;

        $builder->addEventSubscriber(new BiayaPendaftaranSubscriber($em));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran',
        ]);
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_daftarbiayapendaftarantype';
    }
}
