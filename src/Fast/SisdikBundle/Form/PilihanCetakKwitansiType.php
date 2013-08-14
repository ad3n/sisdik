<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PilihanCetakKwitansiType extends AbstractType
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
                ->add('output', 'choice',
                        array(
                                'choices' => $this->buildOutputChoices(), 'required' => true,
                                'expanded' => true, 'multiple' => false
                        ));
    }

    public function buildOutputChoices() {
        return array(
            'pdf' => 'label.defaultpdf', 'esc_p' => 'label.directprint'
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PilihanCetakKwitansi'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_pilihancetakkwitansitype';
    }
}
