<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PersonilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('id', 'hidden');

        $builder
                ->add('user', 'text',
                        array(
                                'label' => 'label.username', 'required' => false,
                                'attr' => array(
                                    'class' => 'large committee-username', 'placeholder' => 'label.username',
                                ), 'label_render' => false,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Personil',
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_personiltype';
    }
}
