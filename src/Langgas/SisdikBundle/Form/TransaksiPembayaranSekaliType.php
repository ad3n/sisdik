<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class TransaksiPembayaranSekaliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nominalPembayaran', 'money', [
                'currency' => 'IDR',
                'required' => true,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium',
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
                'data_class' => 'Langgas\SisdikBundle\Entity\TransaksiPembayaranSekali',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_transaksipembayaransekalitype';
    }
}
