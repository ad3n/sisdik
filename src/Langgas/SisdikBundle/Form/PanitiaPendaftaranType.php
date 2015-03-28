<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\SekolahSubscriber;
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
class PanitiaPendaftaranType extends AbstractType
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

        $builder->addEventSubscriber(new SekolahSubscriber($sekolah));

        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'placeholder' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahun')
                        ->where('tahun.sekolah = :sekolah')
                        ->orderBy('tahun.tahun', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('ketuaPanitia', 'entity', [
                'class' => 'LanggasSisdikBundle:User',
                'label' => 'label.committee.leader',
                'multiple' => false,
                'expanded' => false,
                'property' => 'name',
                'placeholder' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('user')
                        ->where('user.sekolah = :sekolah')
                        ->andWhere('user.siswa IS NULL')
                        ->andWhere('user.sekolah IS NOT NULL')
                        ->orderBy('user.name')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('daftarPersonil', 'collection', [
                'label' => 'label.committee.list',
                'label_render' => true,
                'type' => 'sisdik_personil',
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'widget_add_btn' => [
                    'label' => 'label.committee.add',
                    'attr' => [
                        'class' => 'btn',
                    ],
                    'icon' => 'plus-sign',
                ],
                'options' => [
                    'label_render' => false,
                    'widget_remove_btn' => [
                        'label' => 'label.delete',
                        'attr' => [
                            'class' => 'btn',
                        ],
                        'icon' => 'trash',
                        'wrapper_div' => false,
                    ],
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PanitiaPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_panitiapendaftaran';
    }
}
