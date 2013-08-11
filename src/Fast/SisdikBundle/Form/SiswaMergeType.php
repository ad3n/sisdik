<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaMergeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
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
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswamergetype';
    }
}
