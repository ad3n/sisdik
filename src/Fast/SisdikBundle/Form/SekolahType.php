<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SekolahType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', null,
                        array(
                                'required' => true, 'label' => 'label.schoolname',
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('kode', null,
                        array(
                            'required' => true, 'label' => 'label.code',
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'label' => 'label.address',
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('kodepos', null,
                        array(
                            'label' => 'label.postalcode',
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ))
                ->add('telepon', null,
                        array(
                            'label' => 'label.phone', 
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('fax', null,
                        array(
                            'label' => 'label.fax',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('email', 'email',
                        array(
                                'required' => true, 'label' => 'label.email',
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('norekening', null,
                        array(
                                'label' => 'label.account',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('bank', null,
                        array(
                                'label' => 'label.bank',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('kepsek', null,
                        array(
                                'required' => true, 'label' => 'label.headmaster',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Sekolah'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_sekolahtype';
    }
}
