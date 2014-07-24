<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class ImbalanPendaftaranType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('t')
                ->from('LanggasSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('tahun', 'entity', [
                    'class' => 'LanggasSisdikBundle:Tahun',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'tahun',
                    'empty_value' => false,
                    'required' => true,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'small',
                    ],
                ])
            ;

            $querybuilder2 = $em->createQueryBuilder()
                ->select('t')
                ->from('LanggasSisdikBundle:Gelombang', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('gelombang', 'entity', [
                    'class' => 'LanggasSisdikBundle:Gelombang',
                    'label' => 'label.admissiongroup.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'empty_value' => false,
                    'required' => true,
                    'query_builder' => $querybuilder2,
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
            ;

            $querybuilder3 = $em->createQueryBuilder()
                ->select('t')
                ->from('LanggasSisdikBundle:JenisImbalan', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('jenisimbalan', 'entity', [
                    'class' => 'LanggasSisdikBundle:JenisImbalan',
                    'label' => 'label.reward.type.name',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'empty_value' => false,
                    'required' => true,
                    'query_builder' => $querybuilder3,
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
            ;
        }

        $builder
            ->add('nominal', 'money', [
                'currency' => 'IDR',
                'required' => true,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'large',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Langgas\SisdikBundle\Entity\ImbalanPendaftaran',
        ]);
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_imbalanpendaftarantype';
    }
}
