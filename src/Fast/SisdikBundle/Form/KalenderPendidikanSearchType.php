<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KalenderPendidikanSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('year', 'choice',
                        array(
                                'choices' => $this->buildYearChoices(), 'required' => true,
                                'multiple' => false, 'expanded' => false,
                                'attr' => array(
                                    'class' => 'small'
                                ), 'label_render' => false,
                        ))
                ->add('month', 'choice',
                        array(
                                'choices' => $this->buildMonthChoices(), 'required' => true,
                                'multiple' => false, 'expanded' => false,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false,
                        ));
    }

    public function buildYearChoices() {
        $startyear = 2000;
        for ($i = 0; $i < 301; $i++) {
            $year[$startyear + $i] = $startyear + $i;
        }
        return $year;
    }

    public function buildMonthChoices() {
        return array(
                1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                'September', 'Oktober', 'November', 'Desember'
        );
    }

    public function getName() {
        return 'fast_sisdikbundle_kalenderpendidikansearchtype';
    }

}
