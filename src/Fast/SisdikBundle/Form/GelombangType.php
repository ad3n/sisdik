<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GelombangType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('kode', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ))
                ->add('keterangan', null,
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ))
                ->add('urutan', 'choice',
                        array(
                                'choices' => $this->buildOrderChoices(), 'required' => true,
                                'multiple' => false, 'expanded' => false,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ));

        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Sekolah', 't')->where('t. = :sekolah')
                    ->setParameter('sekolah', $sekolah);
            $builder
                    ->add('sekolah', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Sekolah',
                                    'label' => 'label.school', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder,
                            ));
        }
    }

    public function buildOrderChoices() {
        return array_combine(range(1, 100), range(1, 100));
    }

    public function getName() {
        return 'fast_sisdikbundle_gelombangtype';
    }
}
