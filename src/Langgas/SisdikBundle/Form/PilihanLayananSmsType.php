<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PilihanLayananSmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['daftarLayanan'] as $key => $value) {
            if(array_key_exists($key, $options['layananSmsAktif'])) {
                $builder
                    ->add('jenislayanan_' . $key, 'checkbox', [
                        'label' => $value,
                        'required' => false,
                        'attr' => [
                            'checked' => 'checked',
                        ],
                        'widget_checkbox_label' => 'widget',
                        'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                    ])
                ;
            } else {
                $builder
                    ->add('jenislayanan_' . $key, 'checkbox', [
                        'label' => $value,
                        'required' => false,
                        'widget_checkbox_label' => 'widget',
                        'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                    ])
                ;
            }

        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'daftarLayanan' => [],
                'layananSmsAktif' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_pilihanlayanansms';
    }
}
