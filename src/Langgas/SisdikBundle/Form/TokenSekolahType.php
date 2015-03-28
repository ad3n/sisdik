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
class TokenSekolahType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mesinProxy', 'text', [
                'label' => 'label.token.mesin.proxy',
                'horizontal_input_wrapper_class' => 'col-sm-7 col-md-6 col-lg-5',
                'required' => false,
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
                'data_class' => 'Langgas\SisdikBundle\Entity\TokenSekolah',
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sisdik_tokensekolah';
    }
}
