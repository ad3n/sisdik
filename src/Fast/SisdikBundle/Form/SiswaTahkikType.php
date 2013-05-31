<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SiswaTahkikType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('calonSiswa', 'checkbox',
                        array(
                                'required' => false, 'label_render' => false,
                                'attr' => array(
                                    'class' => 'calon-siswa-check'
                                ),
                        ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Siswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswatahkiktype';
    }
}
