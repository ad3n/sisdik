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
class KelasType extends AbstractType
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
            ->select('tingkat')
            ->from('LanggasSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.tingkat',
                'multiple' => false,
                'expanded' => false,
                'property' => 'optionLabel',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium',
                ],
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('tahunAkademik')
            ->from('LanggasSisdikBundle:TahunAkademik', 'tahunAkademik')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('tahunAkademik.nama', 'DESC')
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
                    'class' => 'medium',
                ],
            ])
        ;

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('nama', 'text', [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('kode', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('keterangan', 'textarea', [
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => false,
            ])
            ->add('urutan', 'choice', [
                'choices' => $this->buildOrderChoices(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'small',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Kelas',
            ])
        ;
    }

    public function buildOrderChoices()
    {
        return array_combine(range(1, 30), range(1, 30));
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_kelastype';
    }
}
