<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KelasDuplicateType extends AbstractType
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
            $querybuilder1 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idtahunSource', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.from', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => true
                            ));

            $builder
                    ->add('idtahunTarget', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.to', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => true
                            ));
        }
    }

    public function getDefaultOptions(array $options) {
        return array(
            'csrf_protection' => false,
        );
    }

    public function getName() {
        return 'fast_sisdikbundle_kelasduplicatetype';
    }
}
