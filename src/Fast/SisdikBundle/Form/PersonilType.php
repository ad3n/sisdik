<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PersonilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden', [
                'label_render' => false,
                'required' => false,
                'attr' => [
                    'class' => 'id-panitia',
                ],
            ])
            ->add('user', 'text', [
                'label' => 'label.username',
                'required' => false,
                'attr' => [
                    'class' => 'xlarge committee-username ketik-pilih-tambah',
                    'placeholder' => 'label.username',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Personil',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_personiltype';
    }
}
