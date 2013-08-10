<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaKelasTemplateMapType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false
                        ));

        $querybuilder = $em->createQueryBuilder()->select('tingkat')
                ->from('FastSisdikBundle:Tingkat', 'tingkat')->where('tingkat.sekolah = :sekolah')
                ->orderBy('tingkat.kode')->setParameter('sekolah', $sekolah);
        $builder
                ->add('tingkat', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tingkat', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'required' => true,
                                'property' => 'optionLabel', 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label_render' => false
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswakelastemplatemaptype';
    }
}
