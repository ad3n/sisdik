<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaSearchType extends AbstractType
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

        $builder
            ->add('tanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.date',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'label' => 'label.searchkey',
                'required' => false,
                'attr' => [
                    'class' => 'search-query medium',
                    'placeholder' => 'label.searchkey',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('tingkat')
            ->from('FastSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->orderBy('tingkat.kode')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tingkat', 'entity', [
                'class' => 'FastSisdikBundle:Tingkat',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium pilih-tingkat',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('FastSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('tahunAkademik.aktif = :aktif')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('aktif', true)
        ;
        $builder
            ->add('kelas', 'entity', [
                'class' => 'FastSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium pilih-kelas',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('statusKehadiran', 'choice', [
                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                'label' => 'label.presence.status.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'label_render' => false,
                'attr' => [
                    'class' => 'medium'
                ],
                'preferred_choices' => [
                    'c-alpa'
                ],
                'empty_value' => 'label.presencestatus',
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_kehadiransiswasearchtype';
    }
}
