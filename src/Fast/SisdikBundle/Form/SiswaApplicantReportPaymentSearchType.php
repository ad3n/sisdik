<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Form\EventListener\JumlahBayarSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaApplicantReportPaymentSearchType extends AbstractType
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

        $querybuilder2 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Gelombang', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah->getId())
        ;

        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => 'label.selectyear',
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'small pilih-tahun',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('gelombang', 'entity', [
                'class' => 'LanggasSisdikBundle:Gelombang',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => 'label.selectadmissiongroup',
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'medium pilih-gelombang',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('dariTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.dari.tanggal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('hinggaTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.hingga.tanggal.singkat',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'attr' => [
                    'class' => 'medium search-query',
                    'placeholder' => 'label.searchkey',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('jenisKelamin', 'choice', [
                'required' => false,
                'choices' => [
                    'L' => 'male',
                    'P' => 'female',
                ],
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'empty_value' => 'label.gender.empty.select',
                'horizontal' => false,
            ])
            ->add('sekolahAsal', new EntityHiddenType($em), [
                'class' => 'LanggasSisdikBundle:SekolahAsal',
                'attr' => [
                    'class' => 'id-sekolah-asal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('namaSekolahAsal', 'text', [
                'attr' => [
                    'class' => 'xlarge nama-sekolah-asal ketik-pilih-tambah',
                    'placeholder' => 'label.sekolah.asal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('referensi', new EntityHiddenType($em), [
                'class' => 'LanggasSisdikBundle:Referensi',
                'attr' => [
                    'class' => 'large id-referensi',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('namaReferensi', 'text', [
                'attr' => [
                    'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                    'placeholder' => 'label.perujuk',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $builder->addEventSubscriber(new JumlahBayarSubscriber($this->container->get('translator')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'searchform';
    }
}
