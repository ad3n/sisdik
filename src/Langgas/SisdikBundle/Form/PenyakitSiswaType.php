<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PenyakitSiswaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nama', 'text', [
                'required' => true,
                'label' => 'label.nama.penyakit',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('kelas', 'text', [
                'required' => false,
                'label' => 'label.terjadi.di.kelas',
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('tahun', 'text', [
                'required' => false,
                'label' => 'label.tahun.sakit',
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('lamasakit', 'text', [
                'required' => false,
                'label' => 'label.lama.sakit',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('keterangan', 'text', [
                'required' => false, 'label' => 'label.keterangan',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PenyakitSiswa',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_penyakitsiswatype';
    }
}
