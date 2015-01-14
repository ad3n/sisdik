<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class CariLaporanKehadiranSiswaType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context")
     * })
     *
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('dariTanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date1 small',
                    'placeholder' => 'dari.tanggal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('hinggaTanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date2 small',
                    'placeholder' => 'hingga.tanggal',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tingkat')
                        ->where('tingkat.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan')
                        ->addOrderBy('tingkat.kode')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium pilih-tingkat',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('kelas')
                        ->leftJoin('kelas.tingkat', 'tingkat')
                        ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                        ->where('kelas.sekolah = :sekolah')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->orderBy('tingkat.urutan')
                        ->addOrderBy('kelas.urutan')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('aktif', true)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium pilih-kelas',
                ],
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
                'empty_value' => 'label.presencestatus',
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_cari_laporankehadiransiswa';
    }
}
