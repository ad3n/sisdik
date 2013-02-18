<?php

namespace Fast\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImbalanPendaftaranType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nominal')
            ->add('jenisImbalan')
            ->add('gelombang')
            ->add('tahunmasuk')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\ImbalanPendaftaran'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_imbalanpendaftarantype';
    }
}
