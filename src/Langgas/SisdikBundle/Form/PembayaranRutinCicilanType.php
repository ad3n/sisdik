<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PembayaranRutinCicilanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('transaksiPembayaranRutin', 'collection', [
                'type' => 'sisdik_transaksipembayaranrutin',
                'by_reference' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.transaksi.pembayaran.biaya.berulang',
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
        return 'sisdik_pembayaranrutincicilan';
    }
}
