<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Validator\Constraints\Length;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TransaksiPembayaranSekaliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nominalPembayaran', 'money',
                        array(
                                'currency' => 'IDR', 'required' => true, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium', 'autocomplete' => 'off'
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
                            'data_class' => 'Fast\SisdikBundle\Entity\TransaksiPembayaranSekali'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_transaksipembayaransekalitype';
    }
}
