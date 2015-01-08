<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaSmsRingkasanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('waliKelas', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:WaliKelas',
                'data' => $options['wali_kelas']->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'required' => true,
                'data' => $options['tanggal'],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'wali_kelas' => null,
                'tanggal' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswasms_ringkasan';
    }
}
