<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaKelasTemplateInitType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()->select('tahun')->from('FastSisdikBundle:Tahun', 'tahun')
                ->where('tahun.sekolah = :sekolah')->orderBy('tahun.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'tahun',
                                'required' => true, 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'small'
                                ), 'label_render' => false
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswakelastemplateinittype';
    }
}
