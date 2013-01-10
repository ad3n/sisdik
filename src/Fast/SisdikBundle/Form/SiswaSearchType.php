<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaSearchType extends AbstractType
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
                    ->from('FastSisdikBundle:Tahunmasuk', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.tahun', 'DESC')->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idtahunmasuk', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahunmasuk',
                                    'label' => 'label.yearentry.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'tahun',
                                    'empty_value' => 'label.selectyear', 'required' => false,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false,
                            ));
        }
        $builder
                ->add('searchkey', null,
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'small search-query'
                                ), 'label_render' => false,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        // return '';
        // return 'fast_sisdikbundle_siswasearchtype';
        return 'searchform';
    }
}
