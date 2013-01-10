<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Entity\Tahun;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WaliKelasType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idtahun', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.year.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium selectyear'
                                    )
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.idjenjang', 't2')
                    ->where('t.idsekolah = :idsekolah')->orderBy('t2.urutan', 'ASC')
                    ->addOrderBy('t.urutan')->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idkelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => false, 'required' => true,
                                    'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'large selectclass'
                                    )
                            ));
        }

        $builder
                ->add('nama', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\WaliKelas'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_walikelastype';
    }
}
