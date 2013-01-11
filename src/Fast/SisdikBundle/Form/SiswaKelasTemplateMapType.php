<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
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

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahun', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.year.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false
                            ));

            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Jenjang', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.kode')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('jenjang', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Jenjang',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'required' => true,
                                    'property' => 'optionLabel', 'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'large'
                                    ), 'label_render' => false
                            ));
        }
    }

    public function getName() {
        return 'fast_sisdikbundle_siswakelastemplatemaptype';
    }
}
