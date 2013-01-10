<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;

class TahunmasukType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('tahun', 'choice',
                        array(
                            'required' => true, 'choices' => $this->buildYearChoices()
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

    public function buildYearChoices() {
        $distance = 5;
        $yearsBefore = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y") - $distance));
        $yearsAfter = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y") + $distance));
        return array_combine(range($yearsBefore, $yearsAfter), range($yearsBefore, $yearsAfter));
    }

    public function getName() {
        return 'fast_sisdikbundle_tahunmasuktype';
    }
}
