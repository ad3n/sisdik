<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class JenisbiayaType extends AbstractType
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
                                    'class' => 'xlarge'
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

    public function getName() {
        return 'fast_sisdikbundle_jenisbiayatype';
    }
}
