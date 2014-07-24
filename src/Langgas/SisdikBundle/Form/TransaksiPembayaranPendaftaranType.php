<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class TransaksiPembayaranPendaftaranType extends AbstractType
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('dibuatOleh', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:User',
                'data' => $user->getId(),
            ])
            ->add('nominalPembayaran', 'money', [
                'currency' => 'IDR',
                'required' => true,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium pay-amount',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.pay.amount',
            ])
            ->add('keterangan', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'xlarge',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.payment.description',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_transaksipembayaranpendaftarantype';
    }
}
