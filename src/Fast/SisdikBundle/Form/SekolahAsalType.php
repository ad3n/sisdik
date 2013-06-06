<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SekolahAsalType extends AbstractType
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
                ->add('nama', 'text',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.nama.sekolah'
                        ))
                ->add('kode', 'text',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'mini'
                                ), 'label' => 'label.kode.sekolah'
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'label' => 'label.alamat'
                        ))
                ->add('penghubung', 'text',
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.nama.penghubung'
                        ))
                ->add('ponselPenghubung', null,
                        array(
                                'label' => 'label.nomor.ponsel.penghubung',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'required' => false,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\SekolahAsal'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_sekolahasaltype';
    }
}
