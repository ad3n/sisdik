<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class OrangtuaWaliInitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('nama', null, [
                'label_render' => true,
                'required' => true,
                'label' => 'label.name.parent.or.guardian',
            ])
            ->add('ponsel', null, [
                'label' => 'label.mobilephone.parent',
                'required' => true,
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => true,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\OrangtuaWali',
            ])
        ;
    }

    public function getName() {
        return 'langgas_sisdikbundle_orangtuawaliinittype';
    }
}
