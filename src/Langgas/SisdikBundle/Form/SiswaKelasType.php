<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Penjurusan;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaKelasType extends AbstractType
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
            ->setParameter('sekolah', $sekolah)
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
        ;

        $querybuilder3 = $em->createQueryBuilder()
            ->select('penjurusan')
            ->from('LanggasSisdikBundle:Penjurusan', 'penjurusan')
            ->where('penjurusan.sekolah = :sekolah')
            ->orderBy('penjurusan.root, penjurusan.lvl', 'ASC')
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
            ->add('penjurusan', 'entity', [
                'class' => 'LanggasSisdikBundle:Penjurusan',
                'label' => 'label.placement.study',
                'multiple' => false,
                'expanded' => false,
                'property' => 'optionLabel',
                'required' => true,
                'query_builder' => $querybuilder3,
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('aktif', null, [
                'label' => 'label.active',
                'required' => false,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
            ->add('keterangan', null, [
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('siswa', new EntityHiddenType($em), [
                'class' => 'LanggasSisdikBundle:Siswa',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\SiswaKelas',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_siswakelastype';
    }
}
