<?php

namespace Fast\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SekolahAsalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nama')
            ->add('kode')
            ->add('alamat')
            ->add('penghubung')
            ->add('ponselPenghubung')
            ->add('sekolah')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\SekolahAsal'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_sekolahasaltype';
    }
}
