<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\Kelas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KepulanganSiswaInisiasiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statusKepulangan', 'choice', [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => JadwalKepulangan::getDaftarStatusKepulangan(),
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('kelas', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Kelas',
                'data' => $options['kelas']->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'data' => $options['tanggal'],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'kelas' => null,
                'tanggal' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kepulangansiswainisiasi';
    }
}
