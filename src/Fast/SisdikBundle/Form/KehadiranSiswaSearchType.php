<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\JadwalKehadiran;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KehadiranSiswaSearchType extends AbstractType
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
                ->add('tanggal', 'date',
                        array(
                                'label' => 'label.date', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.date'
                                ), 'required' => true, 'label_render' => false
                        ))
                ->add('searchkey', null,
                        array(
                                'label' => 'label.searchkey', 'required' => false,
                                'attr' => array(
                                    'class' => 'search-query medium', 'placeholder' => 'label.searchkey'
                                ), 'label_render' => false,
                        ));

        $querybuilder = $em->createQueryBuilder()->select('tingkat')
                ->from('FastSisdikBundle:Tingkat', 'tingkat')->where('tingkat.sekolah = :sekolah')
                ->orderBy('tingkat.kode')->setParameter('sekolah', $sekolah);
        $builder
                ->add('tingkat', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tingkat', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'required' => true,
                                'property' => 'optionLabel', 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'medium pilih-tingkat'
                                ), 'label_render' => false
                        ));

        $querybuilder = $em->createQueryBuilder()->select('kelas')->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                ->where('kelas.sekolah = :sekolah')->andWhere('tahunAkademik.aktif = :aktif')
                ->orderBy('tingkat.urutan', 'ASC')->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $sekolah)->setParameter('aktif', true);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder,
                                'attr' => array(
                                    'class' => 'medium pilih-kelas'
                                ), 'label_render' => false,
                        ));

        $builder
                ->add('statusKehadiran', 'choice',
                        array(
                                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                                'label' => 'label.presence.status.entry', 'multiple' => false,
                                'expanded' => false, 'required' => false, 'label_render' => false,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                                'preferred_choices' => array(
                                    'c-alpa'
                                ), 'empty_value' => 'label.presencestatus'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_kehadiransiswasearchtype';
    }

}
