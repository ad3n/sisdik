<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class KalenderPendidikanSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', 'choice', [
                'choices' => $this->buildYearChoices(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'small',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('month', 'choice', [
                'choices' => $this->buildMonthChoices(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function buildYearChoices()
    {
        $startyear = 2000;
        for ($i = 0; $i < 301; $i ++) {
            $year[$startyear + $i] = $startyear + $i;
        }

        return $year;
    }

    public function buildMonthChoices()
    {
        return [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];
    }

    public function getName()
    {
        return 'fast_sisdikbundle_kalenderpendidikansearchtype';
    }
}
