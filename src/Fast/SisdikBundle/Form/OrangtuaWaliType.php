<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OrangtuaWaliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', null,
                        array(
                                'label_render' => true, 'required' => true,
                                'label' => 'label.name.parent.or.guardian',
                        ))
                ->add('ponsel', null,
                        array(
                                'label' => 'label.mobilephone.parent', 'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('tempatLahir', null,
                        array(
                                'label' => 'label.birthplace',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('tanggalLahir', 'birthday',
                        array(
                                'label' => 'label.birthday', 'widget' => 'single_text',
                                'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small'
                                ), 'required' => false
                        ))
                ->add('kewarganegaraan', null,
                        array(
                                'label' => 'label.nationality',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('hubunganDenganSiswa', 'choice',
                        array(
                                'choices' => $this->buildRelationsToStudent(),
                                'label' => 'label.relation.tostudent',
                        ))
                ->add('pendidikanTertinggi', 'choice',
                        array(
                                'choices' => $this->buildEducationList(),
                                'label' => 'label.highest.education', 'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('pekerjaan', null,
                        array(
                                'label' => 'label.job',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'label' => 'label.address',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ),
                        ))
                ->add('penghasilanBulanan', 'money',
                        array(
                                'currency' => 'IDR', 'required' => false, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium', 'autocomplete' => 'off'
                                ), 'label' => 'label.salary.monthly',
                        ))
                ->add('penghasilanTahunan', 'money',
                        array(
                                'currency' => 'IDR', 'required' => false, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium', 'autocomplete' => 'off'
                                ), 'label' => 'label.salary.annually',
                        ))
                ->add('keterangan', 'textarea',
                        array(
                                'label' => 'label.description',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'required' => false,
                        ));
    }

    private function buildEducationList() {
        return array(
                'SD' => 'SD', 'SLTP' => 'SLTP', 'SLTA' => 'SLTA', 'Diploma' => 'Diploma 1/2/3', 'S1' => 'S1',
                'S2' => 'S2', 'S3' => 'S3'
        );
    }

    private function buildRelationsToStudent() {
        return array(
                'Ayah' => 'Ayah', 'Ibu' => 'Ibu', 'Kakek' => 'Kakek', 'Nenek' => 'Nenek', 'Paman' => 'Paman',
                'Bibi' => 'Bibi', 'Saudara Kandung' => 'Saudara Kandung', 'Lain-lain' => 'Lain-lain'
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\OrangtuaWali'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_orangtuawalitype';
    }
}
