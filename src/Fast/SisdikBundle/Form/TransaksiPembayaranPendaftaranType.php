<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TransaksiPembayaranPendaftaranType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
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
