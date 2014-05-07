<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PembayaranPendaftaranType extends AbstractType
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
                ->add('siswa', new EntityHiddenType($em),
                        array(
                            'required' => true, 'class' => 'FastSisdikBundle:Siswa',
                        ))
                ->add('daftarBiayaPendaftaran', 'collection',
                        array(
                                'type' => new DaftarBiayaPendaftaranType($this->container),
                                'required' => true, 'allow_add' => true, 'allow_delete' => false,
                                'by_reference' => false,
                                'options' => array(
                                    'widget_form_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'widget_form_group' => false,
                        ))
                ->add('adaPotongan', 'checkbox',
                        array(
                                'label' => 'label.discount', 'required' => false,
                                'attr' => array(
                                    'class' => 'discount-check'
                                ), 'widget_checkbox_label' => 'widget',
                        ))
                ->add('jenisPotongan', 'choice',
                        array(
                                'label' => 'label.discount', 'required' => true,
                                'choices' => $this->buildJenisPotongan(), 'expanded' => true,
                                'multiple' => false, 'label_render' => false,
                                'attr' => array(
                                    'class' => 'discount-type'
                                )
                        ))
                ->add('persenPotongan', 'percent',
                        array(
                                'type' => 'integer', 'required' => false, 'precision' => 0,
                                'attr' => array(
                                    'class' => 'small percentage-discount', 'autocomplete' => 'off'
                                ), 'label' => 'label.discount.percentage',
                        ))
                ->add('nominalPotongan', 'money',
                        array(
                                'currency' => 'IDR', 'required' => false, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium nominal-discount', 'autocomplete' => 'off'
                                ), 'label' => 'label.discount.amount',
                        ))
                ->add('transaksiPembayaranPendaftaran', 'collection',
                        array(
                                'type' => new TransaksiPembayaranPendaftaranType($this->container),
                                'by_reference' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.fee.registration.transaction',
                                'options' => array(
                                    'widget_form_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'allow_add' => true, 'allow_delete' => false,
                        ));
    }

    public function buildJenisPotongan() {
        return array(
            'nominal' => 'nominal', 'persentase' => 'persentase'
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PembayaranPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_pembayaranpendaftarantype';
    }
}
