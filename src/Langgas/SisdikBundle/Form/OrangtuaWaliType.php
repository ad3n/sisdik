<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class OrangtuaWaliType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('siswa', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Siswa',
            ])
            ->add('nama', null, [
                'label_render' => true,
                'required' => true,
                'label' => 'label.name.parent.or.guardian',
            ])
            ->add('ponsel', null, [
                'label' => 'label.mobilephone.parent',
                'required' => true,
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('tempatLahir', null, [
                'label' => 'label.birthplace',
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('tanggalLahir', 'birthday', [
                'label' => 'label.birthday',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                ],
                'required' => false
            ])
            ->add('kewarganegaraan', null, [
                'label' => 'label.nationality',
                'attr' => [
                    'class' => 'medium',
                ],
                'required' => false,
            ])
            ->add('hubunganDenganSiswa', 'choice', [
                'choices' => $this->buildRelationsToStudent(),
                'label' => 'label.relation.tostudent',
                'required' => true,
            ])
            ->add('pendidikanTertinggi', 'choice', [
                'choices' => $this->buildEducationList(),
                'label' => 'label.highest.education',
                'attr' => [
                    'class' => 'medium',
                ],
                'required' => true,
            ])
            ->add('pekerjaan', null, [
                'label' => 'label.job',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('alamat', 'textarea', [
                'label' => 'label.address',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('penghasilanBulanan', 'money', [
                'currency' => 'IDR',
                'required' => false,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.salary.monthly',
            ])
            ->add('penghasilanTahunan', 'money', [
                'currency' => 'IDR',
                'required' => false,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.salary.annually',
            ])
            ->add('keterangan', 'textarea', [
                'label' => 'label.description',
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => false,
            ])
        ;
    }

    private function buildEducationList() {
        return [
            'SD' => 'SD',
            'SLTP' => 'SLTP',
            'SLTA' => 'SLTA',
            'Diploma' => 'Diploma 1/2/3',
            'S1' => 'S1',
            'S2' => 'S2',
            'S3' => 'S3',
        ];
    }

    private function buildRelationsToStudent() {
        return [
            'Ayah' => 'Ayah',
            'Ibu' => 'Ibu',
            'Kakek' => 'Kakek',
            'Nenek' => 'Nenek',
            'Paman' => 'Paman',
            'Bibi' => 'Bibi',
            'Saudara Kandung' => 'Saudara Kandung',
            'Lain-lain' => 'Lain-lain',
        ];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\OrangtuaWali',
            ])
        ;
    }

    public function getName() {
        return 'sisdik_orangtuawali';
    }
}
