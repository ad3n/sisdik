<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class MesinWakilType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urlKehadiranManual', 'text', [
                'label' => 'label.url.kehadiran.manual',
                'required' => true,
            ])
            ->add('urlJadwalKehadiran', 'text', [
                'label' => 'label.url.jadwal.kehadiran',
                'required' => true,
            ])
            ->add('urlKepulanganManual', 'text', [
                'label' => 'label.url.kepulangan.manual',
                'required' => true,
            ])
            ->add('urlJadwalKepulangan', 'text', [
                'label' => 'label.url.jadwal.kepulangan',
                'required' => true,
            ])
            ->add('sekolah', 'entity', [
                'class' => 'LanggasSisdikBundle:Sekolah',
                'label' => 'label.school',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) {
                    $qb = $repository->createQueryBuilder('sekolah')
                        ->orderBy('sekolah.nama', 'ASC')
                    ;

                    return $qb;
                },
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\MesinWakil',
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sisdik_mesinwakil';
    }
}
