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
class VendorSekolahType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('jenis', 'choice', [
                'choices' => [
                    'standar' => 'label.vendor.sms.standar.sisdik',
                    'khusus' => 'label.vendor.sms.khusus.pilihan',
                ],
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'disabled' => 'disabled',
                    'class' => 'jenis-vendor',
                ],
                'label_attr' => [
                    'class' => 'jenis-vendor',
                ],
            ])
            ->add('urlPengirimPesan', 'text', [
                'label' => 'label.url.pengirim.pesan',
                'required' => false,
                'attr' => [
                    'class' => 'url-vendor-sms',
                ],
                'help_block' => 'help.url.pengirim.pesan.sms',
            ])
            ->add('sekolah', 'entity', [
                'class' => 'LanggasSisdikBundle:Sekolah',
                'label' => 'label.school',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'placeholder' => false,
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
                'data_class' => 'Langgas\SisdikBundle\Entity\VendorSekolah',
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sisdik_vendorsekolah';
    }
}
