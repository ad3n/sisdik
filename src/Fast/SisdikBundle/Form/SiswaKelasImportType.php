<?php

namespace Fast\SisdikBundle\Form;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaKelasImportType extends AbstractType
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
            $querybuilder1 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:TahunAkademik', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahunAkademik', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:TahunAkademik',
                                    'label' => 'label.year.entry', 'multiple' => false, 'expanded' => false,
                                    'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium selectyear',
                                    ),
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                    ->leftJoin('t.jenjang', 't2')->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                    ->addOrderBy('t.urutan')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => true, 'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'medium selectclass'
                                    ),
                            ));
        }

        $builder
                ->add('delimiter', 'choice',
                        array(
                                'label' => 'label.fielddelimiter',
                                'choices' => array(
                                        ';' => 'semicolon [ ; ]', ',' => 'comma [ , ]', '|' => 'pipe [ | ]',
                                        ':' => 'colon [ : ]'
                                ),
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('file', 'file',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ));
    }

    //     public function setDefaultOptions(OptionsResolverInterface $resolver) {
    //         $resolver
    //                 ->setDefaults(
    //                         array(
    //                             'data_class' => 'Fast\SisdikBundle\Entity\TahunAkademik'
    //                         ));
    //     }

    public function getName() {
        return 'fast_sisdikbundle_siswakelasimporttype';
    }
}
