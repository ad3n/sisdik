<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KalenderPendidikanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', 'hidden', [
                'data' => $options['calendar']['year'],
            ])
            ->add('month', 'hidden', [
                'data' => $options['calendar']['month'],
            ])
        ;

        foreach ($options['calendar']['cal'] as $rows) {
            foreach ($rows as $field) {
                if ($field['num'] == '')
                    continue;
                if ($field['off'] == 1)
                    continue;

                if (array_key_exists($field['num'], $options['activedates'])) {
                    $builder
                        ->add('kbm_' . $field['num'], 'checkbox', [
                            'required' => false,
                            'attr' => [
                                'checked' => 'checked',
                            ],
                            'label_render' => false,
                        ])
                    ;
                } else {
                    $builder
                        ->add('kbm_' . $field['num'], 'checkbox', [
                            'required' => false,
                            'label_render' => false,
                        ])
                    ;
                }
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'calendar' => [],
                'activedates' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kalenderpendidikan';
    }
}
