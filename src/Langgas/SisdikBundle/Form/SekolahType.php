<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SekolahType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nama', null, [
                'required' => true,
                'label' => 'label.schoolname',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('kode', null, [
                'required' => true,
                'label' => 'label.code',
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('alamat', 'textarea', [
                'label' => 'label.address',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('kodepos', null, [
                'label' => 'label.postalcode',
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('telepon', null, [
                'label' => 'label.phone',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('fax', null, [
                'label' => 'label.fax',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('email', 'email', [
                'required' => true,
                'label' => 'label.email',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('norekening', null, [
                'label' => 'label.account',
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('bank', null, [
                'label' => 'label.bank',
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('kepsek', null, [
                'required' => true,
                'label' => 'label.headmaster',
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('fileUpload', 'file', [
                'required' => false,
                'label_render' => true,
                'label' => 'label.logo.sekolah',
                'help_block' => 'help.logo.sekolah',
            ])
            ->add('awalPembiayaan', null, [
                'required' => true,
                'label' => 'label.awal.pembiayaan',
                'attr' => [
                    'maxlength' => 5,
                ],
                'help_block' => 'penjelasan.awal.pembiayaan',
            ])
            ->add('akhirPembiayaan', null, [
                'required' => true,
                'label' => 'label.akhir.pembiayaan',
                'attr' => [
                    'maxlength' => 5,
                ],
                'help_block' => 'penjelasan.akhir.pembiayaan',
            ])
            ->add('atributNomorDaftar', null, [
                'required' => false,
                'label' => 'label.atribut.nomor.pendaftaran',
                'help_block' => 'penjelasan.atribut.nomor.pendaftaran',
                'attr' => [
                    'placeholder' => 'contoh.atribut.nomor.pendaftaran',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Sekolah',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_sekolah';
    }
}
