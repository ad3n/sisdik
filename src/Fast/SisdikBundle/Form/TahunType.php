<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TahunType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('tahun', 'choice',
                        array(
                                'required' => true, 'choices' => $this->buildYearChoices(),
                                'attr' => array(
                                    'class' => 'small'
                                )
                        ));
    }

    public function buildYearChoices() {
        $distance = 5;
        $yearsBefore = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y") - $distance));
        $yearsAfter = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y") + $distance));

        return array_combine(range($yearsBefore, $yearsAfter), range($yearsBefore, $yearsAfter));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Tahun'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_tahuntype';
    }
}
