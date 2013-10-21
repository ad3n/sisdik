<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;

class PembayaranPendaftaranCicilanType extends AbstractType
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')
            ->getToken()
            ->getUser();
        $builder->add('transaksiPembayaranPendaftaran', 'collection', array(
            'type' => new TransaksiPembayaranPendaftaranType($this->container),
            'by_reference' => false,
            'attr' => array(
                'class' => 'large'
            ),
            'label' => 'label.fee.registration.transaction',
            'options' => array(
                'widget_control_group' => false,
                'label_render' => false
            ),
            'label_render' => false,
            'allow_add' => true,
            'allow_delete' => true
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\PembayaranPendaftaran'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_pembayaranpendaftarantype';
    }
}
