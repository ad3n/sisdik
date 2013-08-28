<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Kelas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KehadiranSiswaInisiasiType extends AbstractType
{
    private $container;
    private $kelas;
    private $tanggal;

    public function __construct(ContainerInterface $container, Kelas $kelas, $tanggal) {
        $this->container = $container;
        $this->kelas = $kelas;
        $this->tanggal = $tanggal;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('statusKehadiran', 'choice',
                        array(
                                'required' => true, 'expanded' => false, 'multiple' => false,
                                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('kelas', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Kelas',
                                'data' => $this->kelas->getId(),
                        ))
                ->add('tanggal', 'hidden',
                        array(
                            'data' => $this->tanggal
                        ));

    }

    public function getName() {
        return 'fast_sisdikbundle_kehadiransiswainisiasitype';
    }
}
