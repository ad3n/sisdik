<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PenyakitSiswaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', 'text',
                        array(
                                'required' => true, 'label' => 'label.nama.penyakit',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('kelas', 'text',
                        array(
                                'required' => false, 'label' => 'label.terjadi.di.kelas',
                                'attr' => array(
                                    'class' => 'small'
                                ),
                        ))
                ->add('tahun', 'text',
                        array(
                                'required' => false, 'label' => 'label.tahun.sakit',
                                'attr' => array(
                                    'class' => 'small'
                                ),
                        ))
                ->add('lamasakit', 'text',
                        array(
                                'required' => false, 'label' => 'label.lama.sakit',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('keterangan', 'text',
                        array(
                                'required' => false, 'label' => 'label.keterangan',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ),
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PenyakitSiswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_penyakitsiswatype';
    }
}
