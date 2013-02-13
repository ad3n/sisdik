<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JenisImbalanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', 'choice',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'choices' => $this->buildNamaJenisImbalan()
                        ))->add('keterangan')->add('sekolah');
    }

    static function buildNamaJenisImbalan() {
        $array = array(
            'kolektif' => 'kolektif', 'individu' => 'individu',
        );
        asort($array);

        return $array;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\JenisImbalan'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_jenisimbalantype';
    }
}
