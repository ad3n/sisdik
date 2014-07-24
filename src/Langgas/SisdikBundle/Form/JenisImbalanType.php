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
class JenisImbalanType extends AbstractType
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
            ->add('nama', 'choice', [
                'required' => true,
                'label' => 'label.reward.type.name',
                'attr' => [
                    'class' => 'medium',
                ],
                'choices' => $this->buildNamaJenisImbalan(),
            ])
            ->add('keterangan', null, [
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;
    }

    /**
     * @return array
     */
    public static function buildNamaJenisImbalan()
    {
        $array = [
            'kolektif' => 'kolektif',
            'individu' => 'individu',
        ];
        asort($array);

        return $array;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\JenisImbalan',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_jenisimbalantype';
    }
}
