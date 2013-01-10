<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JenjangType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('kode', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ))
                ->add('nama', null,
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'large'
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
        $idsekolah = $user->getIdsekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Sekolah', 't')->where('t.id = :idsekolah')
                    ->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idsekolah', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Sekolah',
                                    'label' => 'label.school', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder,
                            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Jenjang'
                        ));
    }

    public function buildOrderChoices() {
        return array_combine(range(1, 10), range(1, 10));
    }

    public function getName() {
        return 'fast_sisdikbundle_jenjangtype';
    }
}
