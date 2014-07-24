<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaKelasTemplateMapType extends AbstractType
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
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('tingkat')
            ->from('LanggasSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->orderBy('tingkat.kode')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'large',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_siswakelastemplatemaptype';
    }
}
