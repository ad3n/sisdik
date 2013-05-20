<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReferensiType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('nama', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.name.full'
                        ))
                ->add('ponsel', null,
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label' => 'label.ponsel'
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.alamat'
                        ))
                ->add('nomorIdentitas', 'text',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.nomor.identitas'
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Referensi'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_referensitype';
    }
}
