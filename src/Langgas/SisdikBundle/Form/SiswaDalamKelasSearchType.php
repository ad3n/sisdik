<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SiswaDalamKelasSearchType extends AbstractType
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

        if ($options['mode_wali_kelas'] !== true) {
            $builder
                ->add('tahunAkademik', 'entity', [
                    'class' => 'LanggasSisdikBundle:TahunAkademik',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('tahunAkademik')
                            ->where('tahunAkademik.sekolah = :sekolah')
                            ->orderBy('tahunAkademik.urutan', 'DESC')
                            ->addOrderBy('tahunAkademik.nama', 'DESC')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium pilih-tahun',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
                ->add('kelas', 'entity', [
                    'class' => 'LanggasSisdikBundle:Kelas',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('kelas')
                            ->leftJoin('kelas.tingkat', 'tingkat')
                            ->where('kelas.sekolah = :sekolah')
                            ->orderBy('tingkat.urutan', 'ASC')
                            ->addOrderBy('kelas.urutan', 'DESC')
                            ->addOrderBy('kelas.nama', 'DESC')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium pilih-kelas',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        } else {
            $kelas = $options['kelas_id'];

            $builder
                ->add('kelas', 'entity', [
                    'class' => 'LanggasSisdikBundle:Kelas',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah, $kelas) {
                        $qb = $repository->createQueryBuilder('kelas')
                            ->leftJoin('kelas.tingkat', 'tingkat')
                            ->where('kelas.sekolah = :sekolah')
                            ->andWhere('kelas.id IN (?1)')
                            ->orderBy('tingkat.urutan', 'ASC')
                            ->addOrderBy('kelas.urutan', 'DESC')
                            ->addOrderBy('kelas.nama', 'DESC')
                            ->setParameter('sekolah', $sekolah)
                            ->setParameter(1, $kelas)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium pilih-kelas',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        }

        $builder
            ->add('searchkey', null, [
                'label' => 'label.searchkey',
                'required' => false,
                'attr' => [
                    'class' => 'medium search-query',
                    'placeholder' => 'kata.pencarian',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
                'mode_wali_kelas' => false,
                'kelas_id' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_carisiswadikelas';
    }
}
