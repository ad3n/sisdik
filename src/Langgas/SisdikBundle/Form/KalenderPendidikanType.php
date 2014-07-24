<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KalenderPendidikanType extends AbstractType
{
    /**
     * @var array
     */
    private $calendar = [];

    /**
     * @var array
     */
    private $activedates = [];

    /**
     * @param array $calendar
     * @param array $activedates
     */
    public function __construct($calendar = [], $activedates = [])
    {
        $this->calendar = $calendar;
        $this->activedates = $activedates;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', 'hidden', [
                'data' => $this->calendar['year'],
            ])
            ->add('month', 'hidden', [
                'data' => $this->calendar['month'],
            ])
        ;

        foreach ($this->calendar['cal'] as $rows) {
            foreach ($rows as $field) {
                if ($field['num'] == '')
                    continue;
                if ($field['off'] == 1)
                    continue;

                if (array_key_exists($field['num'], $this->activedates)) {
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

    public function getName()
    {
        return 'sisdik_kalenderpendidikan';
    }
}
