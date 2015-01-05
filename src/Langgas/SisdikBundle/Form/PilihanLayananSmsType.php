<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PilihanLayananSmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('jenisLayanan', 'choice', [
                'choices' => array_merge(
                    PilihanLayananSms::getDaftarLayananPendaftaran(),
                    PilihanLayananSms::getDaftarLayananLaporan(),
                    PilihanLayananSms::getDaftarLayananKehadiran(),
                    PilihanLayananSms::getDaftarLayananKepulangan(),
                    PilihanLayananSms::getDaftarLayananBiayaSekaliBayar(),
                    PilihanLayananSms::getDaftarLayananLain()
                ),
                'required' => true,
                'label' => 'label.layanansms.jenis',
            ])
            ->add('status', 'checkbox', [
                'required' => false,
                'label' => 'label.aktif',
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PilihanLayananSms',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_pilihanlayanansms';
    }
}
