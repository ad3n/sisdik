<?php

namespace Fast\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrangtuaWaliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nama')
            ->add('tempatLahir')
            ->add('tanggalLahir')
            ->add('ponsel')
            ->add('kewarganegaraan')
            ->add('hubunganDenganSiswa')
            ->add('pendidikanTertinggi')
            ->add('pekerjaan')
            ->add('penghasilanBulanan')
            ->add('penghasilanTahunan')
            ->add('alamat')
            ->add('keterangan')
            ->add('aktif')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\OrangtuaWali'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_orangtuawalitype';
    }
}
