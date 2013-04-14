<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LayananSmsPendaftaranType extends AbstractType
{

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Sekolah', 't')
                ->where('t.id = :sekolah')->setParameter('sekolah', $sekolah);
        $builder
                ->add('sekolah', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Sekolah', 'label' => 'label.school',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'empty_value' => false, 'required' => true, 'query_builder' => $querybuilder,
                        ));

        $builder
                ->add('jenisLayanan', 'choice',
                        array(
                                'choices' => PilihanLayananSms::getDaftarLayanan(), 'required' => true,
                                'label' => 'label.layanansms.jenis',
                        ));

        $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Templatesms', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());
        $builder
                ->add('templatesms', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Templatesms',
                                'label' => 'label.sms.template.entry', 'multiple' => false,
                                'expanded' => false, 'required' => true, 'property' => 'optionLabel',
                                'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\LayananSmsPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_layanansmspendaftarantype';
    }
}
