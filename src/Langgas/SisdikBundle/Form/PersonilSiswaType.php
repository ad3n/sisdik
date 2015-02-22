<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PersonilSiswaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden', [
                'label_render' => false,
                'required' => false,
                'attr' => [
                    'class' => 'id-personil',
                ],
            ])
            ->add('namaSiswa', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'nama-personil ketik-pilih-tambah',
                    'placeholder' => 'label.cari.siswa',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PersonilSiswa',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_personilsiswa';
    }
}
