<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PembayaranRutinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('transaksiPembayaranRutin', 'collection', [
                'type' => 'sisdik_transaksipembayaranrutin',
                'by_reference' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.transaksi.pembayaran.berulang',
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
                'data_class' => 'Langgas\SisdikBundle\Entity\PembayaranRutin',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_pembayaranrutin';
    }
}
