<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class BiayaSekaliType extends AbstractType
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
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => false,
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
            ->add('jenisbiaya', 'entity', [
                'class' => 'LanggasSisdikBundle:Jenisbiaya',
                'label' => 'label.fee.type.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('jenisbiaya')
                        ->where('jenisbiaya.sekolah = :sekolah')
                        ->orderBy('jenisbiaya.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('nominal', 'money', [
                'currency' => 'IDR',
                'required' => true,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'large',
                ],
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

    /**
     * @return array
     */
    public function buildOrderChoices()
    {
        return array_combine(range(1, 100), range(1, 100));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\BiayaSekali',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_biayasekali';
    }
}
