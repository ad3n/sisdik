<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Entity\Sekolah;
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

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('jenisLayanan', 'choice',
                        array(
                                'choices' => PilihanLayananSms::getDaftarLayananPendaftaran(),
                                'required' => true, 'label' => 'label.layanansms.jenis',
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
