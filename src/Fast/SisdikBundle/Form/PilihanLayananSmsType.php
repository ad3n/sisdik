<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PilihanLayananSmsType extends AbstractType
{

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('FastSisdikBundle:Sekolah', 't')
            ->orderBy('t.nama', 'ASC');
        $builder->add('sekolah', 'entity', array(
            'class' => 'FastSisdikBundle:Sekolah',
            'label' => 'label.school',
            'multiple' => false,
            'expanded' => false,
            'property' => 'nama',
            'empty_value' => false,
            'required' => true,
            'query_builder' => $querybuilder
        ));
        $builder->add('jenisLayanan', 'choice', array(
            'choices' => array_merge(PilihanLayananSms::getDaftarLayananPendaftaran(), PilihanLayananSms::getDaftarLayananLaporan(), PilihanLayananSms::getDaftarLayananKehadiran()),
            'required' => true,
            'label' => 'label.layanansms.jenis'
        ))->add('status', 'checkbox', array(
            'required' => false,
            'label' => 'label.aktif',
            'widget_checkbox_label' => 'widget'
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\PilihanLayananSms'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_pilihanlayanansmstype';
    }
}
