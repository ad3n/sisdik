<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KalenderPendidikanType extends AbstractType
{
    private $calendar = array();
    private $activedates = array();

    public function __construct($calendar = array(), $activedates = array()) {
        $this->calendar = $calendar;
        $this->activedates = $activedates;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('year', 'hidden',
                        array(
                            'data' => $this->calendar['year']
                        ))
                ->add('month', 'hidden',
                        array(
                            'data' => $this->calendar['month']
                        ));

        foreach ($this->calendar['cal'] as $rows) {
            foreach ($rows as $field) {
                if ($field['num'] == '')
                    continue;
                if ($field['off'] == 1)
                    continue;

                if (array_key_exists($field['num'], $this->activedates)) {
                    $builder
                            ->add('kbm_' . $field['num'], 'checkbox',
                                    array(
                                            'required' => false,
                                            'attr' => array(
                                                'checked' => 'checked'
                                            ), 'label_render' => false,
                                    ));
                } else {
                    $builder
                            ->add('kbm_' . $field['num'], 'checkbox',
                                    array(
                                        'required' => false, 'label_render' => false,
                                    ));
                }
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        //         $resolver->setDefaults(array(
        //             'data_class' => 'Fast\SisdikBundle\Entity\KalenderPendidikan'
        //         ));
    }

    public function getName() {
        return 'fast_sisdikbundle_kalenderpendidikantype';
    }
}
