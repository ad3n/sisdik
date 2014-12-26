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
class PenjurusanType extends AbstractType
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
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('nama', null, [
                'required' => true,
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('kode', null, [
                'required' => true,
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('kepala', null, [
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('parent', 'entity', [
                'class' => 'LanggasSisdikBundle:Penjurusan',
                'label' => 'label.parentnode',
                'multiple' => false,
                'expanded' => false,
                'property' => 'optionLabel',
                'empty_value' => 'label.select.parentnode',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('penjurusan')
                        ->where('penjurusan.sekolah = :sekolah')
                        ->orderBy('penjurusan.sekolah', 'ASC')
                        ->addOrderBy('penjurusan.root', 'ASC')
                        ->addOrderBy('penjurusan.lft', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Penjurusan',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_penjurusan';
    }
}
