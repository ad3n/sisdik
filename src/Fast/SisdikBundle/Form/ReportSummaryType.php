<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportSummaryType extends AbstractType
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
                ->add('output', 'hidden',
                        array(
                            'required' => false, 'label_render' => false,
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
                ->add('nomorPonsel', 'text',
                        array(
                                'label' => 'label.ponsel',
                                'attr' => array(
                                    'class' => 'large',
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
