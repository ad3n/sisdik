<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PilihanKirimSmsType extends AbstractType
{
    private $container;
    private $mode;

    public function __construct(ContainerInterface $container, $mode = 'edit') {
        $this->container = $container;
        $this->mode = $mode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        if ($this->mode == 'new') {
            $pilihan = $em->getRepository('FastSisdikBundle:PilihanKirimSms')->findAll();
            $schoolarray = array();
            foreach ($pilihan as $p) {
                $schoolarray[] = $p->getSekolah()->getId();
            }

            if (count($schoolarray) >= 1) {
                $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Sekolah', 't')
                        ->where('t.id NOT IN (?1)')->setParameter(1, $schoolarray);
            } else {
                $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Sekolah', 't');
            }
        } else {
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Sekolah', 't');
        }
        $builder
                ->add('sekolah', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Sekolah', 'label' => 'label.school',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'empty_value' => false, 'required' => true, 'query_builder' => $querybuilder,
                        ));

        $builder
                ->add('pendaftaranTercatat', 'checkbox',
                        array(
                                'label' => 'label.applicant.recorded', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('pendaftaranBayarPertama', 'checkbox',
                        array(
                                'label' => 'label.registrationfee.firstpay', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('pendaftaranBayar', 'checkbox',
                        array(
                                'label' => 'label.registrationfee.pay', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('pendaftaranBayarLunas', 'checkbox',
                        array(
                                'label' => 'label.registrationfee.settled', 'required' => false,
                                'widget_checkbox_label' => 'widget',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PilihanKirimSms'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_pilihankirimsmstype';
    }
}
