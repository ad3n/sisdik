<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class WaliKelasType extends AbstractType
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

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:TahunAkademik', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.urutan', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahunAkademik', 'entity', [
                'class' => 'LanggasSisdikBundle:TahunAkademik',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium selectyear',
                ],
            ])
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Kelas', 't')
            ->leftJoin('t.tingkat', 't2')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t2.urutan', 'ASC')
            ->addOrderBy('t.urutan')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'large selectclass',
                ],
            ])
            ->add('nama', null, [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\WaliKelas',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_walikelastype';
    }
}
