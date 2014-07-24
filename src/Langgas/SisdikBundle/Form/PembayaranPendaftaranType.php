<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PembayaranPendaftaranType extends AbstractType
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
            ->add('siswa', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Siswa',
            ])
            ->add('daftarBiayaPendaftaran', 'collection', [
                'type' => new DaftarBiayaPendaftaranType($this->container),
                'required' => true,
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'options' => [
                    'widget_form_group' => false,
                    'label_render' => false,
                ],
                'label_render' => false,
                'widget_form_group' => false,
            ])
            ->add('adaPotongan', 'checkbox', [
                'label' => 'label.discount',
                'required' => false,
                'attr' => [
                    'class' => 'discount-check',
                ],
                'widget_checkbox_label' => 'widget',
            ])
            ->add('jenisPotongan', 'choice', [
                'label' => 'label.discount',
                'required' => true,
                'choices' => $this->buildJenisPotongan(),
                'expanded' => true,
                'multiple' => false,
                'label_render' => false,
                'attr' => [
                    'class' => 'discount-type',
                ],
            ])
            ->add('persenPotongan', 'percent', [
                'type' => 'integer',
                'required' => false,
                'precision' => 0,
                'attr' => [
                    'class' => 'small percentage-discount',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.discount.percentage',
            ])
            ->add('nominalPotongan', 'money', [
                'currency' => 'IDR',
                'required' => false,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium nominal-discount',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.discount.amount',
            ])
            ->add('transaksiPembayaranPendaftaran', 'collection', [
                'type' => new TransaksiPembayaranPendaftaranType($this->container),
                'by_reference' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.fee.registration.transaction',
                'options' => [
                    'widget_form_group' => false,
                    'label_render' => false,
                ],
                'label_render' => false,
                'allow_add' => true,
                'allow_delete' => false,
            ])
        ;
    }

    public function buildJenisPotongan()
    {
        return [
            'nominal' => 'nominal',
            'persentase' => 'persentase',
        ];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PembayaranPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_pembayaranpendaftarantype';
    }
}
