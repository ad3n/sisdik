<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class ReportSummaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('output', 'hidden', [
                'data' => 'pdf',
                'attr' => [
                    'class' => 'output-ringkasan',
                ],
                'required' => true,
                'label_render' => false,
            ])
            ->add('teks', 'textarea', [
                'label' => 'label.teks.ringkasan',
                'attr' => [
                    'class' => 'xlarge ringkasan-teks',
                ],
                'help_block' => 'help.tag.standar.laporan.psb',
                'label_attr' => [
                    'class' => 'label-ringkasan-teks'
                ],
                'required' => true,
                'label_render' => true,
                'horizontal_label_class' => '',
            ])
            ->add('teksTerformat', 'hidden', [
                'attr' => [
                    'class' => 'teks-terformat',
                ],
                'required' => true,
                'label_render' => false,
            ])
            ->add('nomorPonsel', 'text', [
                'label' => 'label.ponsel',
                'attr' => array(
                    'class' => 'large nomor-ponsel',
                    'placeholder' => 'label.perlu.untuk.sms',
                ),
                'required' => false,
                'label_render' => true,
                'horizontal_label_class' => '',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'siswaapplicantreportsummary';
    }
}
