<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JenisImbalanType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('nama', 'choice',
                        array(
                                'required' => true, 'label' => 'label.reward.type.name',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'choices' => $this->buildNamaJenisImbalan()
                        ))
                ->add('keterangan', null,
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                ),
                        ));

    }

    static function buildNamaJenisImbalan() {
        $array = array(
            'kolektif' => 'kolektif', 'individu' => 'individu',
        );
        asort($array);

        return $array;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\JenisImbalan'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_jenisimbalantype';
    }
}
