<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportSummaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('output', 'hidden',
                        array(
                                'data' => 'pdf',
                                'attr' => array(
                                    'class' => 'output-ringkasan'
                                ), 'required' => true, 'label_render' => false,
                        ))
                ->add('teks', 'textarea',
                        array(
                                'label' => 'label.teks.ringkasan',
                                'attr' => array(
                                    'class' => 'xlarge ringkasan-teks',
                                ), 'help_block' => 'help.tag.standar.laporan.psb',
                                'label_attr' => array(
                                    'class' => 'label-ringkasan-teks'
                                ), 'required' => true, 'label_render' => true,
                        ))
                ->add('teksTerformat', 'hidden',
                        array(
                                'attr' => array(
                                    'class' => 'teks-terformat'
                                ), 'required' => true, 'label_render' => false,
                        ))
                ->add('nomorPonsel', 'text',
                        array(
                                'label' => 'label.ponsel',
                                'attr' => array(
                                    'class' => 'large nomor-ponsel', 'placeholder' => 'label.perlu.untuk.sms',
                                ), 'required' => false, 'label_render' => true,
                        ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'siswaapplicantreportsummary';
    }
}
