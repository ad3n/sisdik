<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PenempatanSiswaKelasType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        $querybuilder1 = $em->createQueryBuilder()->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('tahunAkademik.nama', 'DESC')->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium selectyear',
                                ),
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('kelas')->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')->where('kelas.sekolah = :sekolah')
                ->orderBy('tingkat.urutan', 'ASC')->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium selectclass'
                                ),
                        ));

        $builder
                ->add('file', 'file',
                        array(
                            'required' => true,
                        ))
                ->add('captcha', 'captcha',
                        array(
                                'attr' => array(
                                        'class' => 'medium', 'placeholder' => 'help.type.captcha',
                                        'autocomplete' => 'off'
                                ), 'as_url' => true, 'reload' => true,
                                'help_block' => 'help.captcha.penjelasan.unggah.siswa.kelas',
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_penempatansiswakelastype';
    }
}
