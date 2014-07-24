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
class JenisDokumenSiswaType extends AbstractType
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
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'small',
                ],
            ])
        ;

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('namaDokumen', 'text', [
                'label' => 'label.jenis.dokumen.siswa',
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => true,
            ])
            ->add('keterangan', 'textarea', [
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => false,
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
                'data_class' => 'Langgas\SisdikBundle\Entity\JenisDokumenSiswa',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_jenisdokumensiswatype';
    }
}
