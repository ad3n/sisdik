<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Entity\Tahun;
use Fast\SisdikBundle\Entity\Penjurusan;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SiswaKelasType extends AbstractType
{
    private $container;
    private $siswa;

    public function __construct(ContainerInterface $container, $siswa) {
        $this->container = $container;
        $this->siswa = $siswa;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahun', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.year.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium selectyear'
                                    )
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.jenjang', 't2')
                    ->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                    ->addOrderBy('t.urutan')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'large selectclass'
                                    )
                            ));

            $querybuilder3 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Penjurusan', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.root, t.lvl', 'ASC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('penjurusan', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Penjurusan',
                                    'label' => 'label.placement.study', 'multiple' => false,
                                    'expanded' => false, 'property' => 'optionLabel',
                                    'required' => true, 'query_builder' => $querybuilder3,
                                    'attr' => array(
                                        'class' => 'xlarge'
                                    )
                            ));
        }

        $querybuilder3 = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:Siswa', 't')->where('t. = :siswa')
                ->setParameter('siswa', $this->siswa);
        $builder
                ->add('siswa', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Siswa',
                                'label' => 'label.student.entry', 'multiple' => false,
                                'expanded' => false, 'property' => 'namaLengkap',
                                'empty_value' => false, 'required' => true,
                                'query_builder' => $querybuilder3,
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));

        $builder
                ->add('aktif', null,
                        array(
                            'label' => 'label.active', 'required' => false, 'label_render' => false
                        ))
                ->add('keterangan', null,
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\SiswaKelas'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswakelastype';
    }
}
