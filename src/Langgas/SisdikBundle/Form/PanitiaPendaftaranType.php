<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PanitiaPendaftaranType extends AbstractType
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Tahun', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah->getId())
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
            ->from('LanggasSisdikBundle:User', 't')
            ->where('t.sekolah = :sekolah')
            ->andWhere('t.siswa IS NULL')
            ->andWhere('t.sekolah IS NOT NULL')
            ->orderBy('t.name')
            ->setParameter('sekolah', $sekolah->getId())
        ;
        $builder
            ->add('ketuaPanitia', 'entity', [
                'class' => 'LanggasSisdikBundle:User',
                'label' => 'label.committee.leader',
                'multiple' => false,
                'expanded' => false,
                'property' => 'name',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('daftarPersonil', 'collection', [
                'label' => 'label.committee.list',
                'label_render' => true,
                'type' => new PersonilType(),
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'widget_add_btn' => [
                    'label' => 'label.committee.add',
                    'attr' => [
                        'class' => 'btn',
                    ],
                    'icon' => 'plus-sign',
                ],
                'options' => [
                    'label_render' => false,
                    'widget_remove_btn' => [
                        'label' => 'label.delete',
                        'attr' => [
                            'class' => 'btn',
                        ],
                        'icon' => 'trash',
                        'wrapper_div' => false,
                    ],
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PanitiaPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_panitiapendaftarantype';
    }
}
