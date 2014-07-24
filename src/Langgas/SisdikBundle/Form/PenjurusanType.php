<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PenjurusanType extends AbstractType
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

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
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
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('penjurusan')
            ->from('LanggasSisdikBundle:Penjurusan', 'penjurusan')
            ->where('penjurusan.sekolah = :sekolah')
            ->orderBy('penjurusan.sekolah ASC, penjurusan.root, penjurusan.lft', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('parent', 'entity', [
                'class' => 'LanggasSisdikBundle:Penjurusan',
                'label' => 'label.parentnode',
                'multiple' => false,
                'expanded' => false,
                'property' => 'optionLabel',
                'empty_value' => 'label.select.parentnode',
                'required' => false,
                'query_builder' => $querybuilder,
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
        return 'langgas_sisdikbundle_penjurusantype';
    }
}
