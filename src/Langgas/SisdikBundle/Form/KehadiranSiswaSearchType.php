<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class KehadiranSiswaSearchType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @InjectParams({
     *     "tokenStorage" = @Inject("security.token_storage")
     * })
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

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

        $builder
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
        ;

        $builder
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
                'placeholder' => 'label.presencestatus',
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswasearch';
    }
}
