<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\JumlahBayarSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaApplicantPaymentReportSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah->getId());
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'tahun',
                                'empty_value' => 'label.selectyear', 'required' => false,
                                'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'small'
                                ), 'label_render' => false,
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Gelombang', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah->getId());
        $builder
                ->add('gelombang', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Gelombang', 'multiple' => false,
                                'expanded' => false, 'property' => 'nama',
                                'empty_value' => 'label.selectadmissiongroup',
                                'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'required' => false, 'label_render' => false,
                        ));

        $builder
                ->add('dariTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.dari.tanggal'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('hinggaTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.hingga.tanggal.singkat'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('searchkey', null,
                        array(
                                'attr' => array(
                                    'class' => 'medium search-query', 'placeholder' => 'label.searchkey'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('sekolahAsal', new EntityHiddenType($em),
                        array(
                                'class' => 'FastSisdikBundle:SekolahAsal',
                                'attr' => array(
                                    'class' => 'id-sekolah-asal'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('namaSekolahAsal', 'text',
                        array(
                                'attr' => array(
                                        'class' => 'xlarge nama-sekolah-asal ketik-pilih-tambah',
                                        'placeholder' => 'label.sekolah.asal',
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('referensi', new EntityHiddenType($em),
                        array(
                                'class' => 'FastSisdikBundle:Referensi',
                                'attr' => array(
                                    'class' => 'large id-referensi'
                                ), 'required' => false, 'label_render' => false,
                        ))
                ->add('namaReferensi', 'text',
                        array(
                                'attr' => array(
                                        'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                                        'placeholder' => 'label.perujuk',
                                ), 'required' => false, 'label_render' => false,
                        ));

        $builder->addEventSubscriber(new JumlahBayarSubscriber($this->container->get('translator')));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'searchform';
    }
}
