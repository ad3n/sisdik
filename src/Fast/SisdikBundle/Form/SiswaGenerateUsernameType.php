<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaGenerateUsernameType extends AbstractType
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
                    ->from('FastSisdikBundle:Tahun', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.urutan', 'DESC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahun', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahun',
                                    'label' => 'label.year.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium selectyear'
                                    ),
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.jenjang', 't2')
                    ->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                    ->addOrderBy('t.urutan')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => true,
                                    'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'medium selectclass'
                                    ),
                            ));

            $builder
                    ->add('output', 'choice',
                            array(
                                    'choices' => array(
                                        'ods' => 'Open Document Spreadsheet', 'xls' => 'Microsoft Excel 97/2000/XP'
                                    ), 'label' => 'label.output', 'multiple' => false,
                                    'expanded' => true, 'required' => true,
                            ));
        }
    }

    public function getName() {
        return 'fast_sisdikbundle_siswagenerateusernametype';
    }
}

