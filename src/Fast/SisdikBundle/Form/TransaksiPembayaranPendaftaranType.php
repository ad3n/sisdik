<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransaksiPembayaranPendaftaranType extends AbstractType
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
                ->add('dibuatOleh', new EntityHiddenType($em),
                        array(
                            'required' => true, 'class' => 'FastSisdikBundle:User', 'data' => $user->getId(),
                        ))
                ->add('nominalPembayaran', 'money',
                        array(
                                'currency' => 'IDR', 'required' => true, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium pay-amount', 'autocomplete' => 'off'
                                ), 'label' => 'label.pay.amount',
                        ))
                ->add('keterangan', 'text',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'xlarge', 'autocomplete' => 'off'
                                ), 'label' => 'label.payment.description',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_transaksipembayaranpendaftarantype';
    }
}
