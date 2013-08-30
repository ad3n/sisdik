<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\KehadiranSiswa;

use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Kelas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KehadiranSiswaSmsType extends AbstractType
{
    private $container;
    private $kelas;
    private $tanggal;
    private $entitiesKehadiran;

    public function __construct(ContainerInterface $container, Kelas $kelas, $tanggal, $entitiesKehadiran) {
        $this->container = $container;
        $this->kelas = $kelas;
        $this->tanggal = $tanggal;
        $this->entitiesKehadiran = $entitiesKehadiran;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('statusKehadiran', 'choice',
                        array(
                                'required' => true, 'expanded' => false, 'multiple' => false,
                                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'empty_value' => 'label.pilih.status.kehadiran',
                        ))
                ->add('kelas', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Kelas',
                                'data' => $this->kelas->getId(),
                        ))
                ->add('tanggal', 'hidden',
                        array(
                            'data' => $this->tanggal
                        ))
                ->add('keterangan', 'textarea',
                        array(
                                'label' => 'label.keterangan',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'required' => false,
                                'help_block' => 'Keterangan hanya digunakan untuk izin dan sakit. Dan siswa harus dipilih.'
                        ));

        $siswa = array();
        foreach ($this->entitiesKehadiran as $kehadiran) {
            if ($kehadiran instanceof KehadiranSiswa) {
                $siswa[] = $kehadiran->getSiswa()->getId();
            }
        }

        $querybuilder1 = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->where('siswa.id IN (:id)')->orderBy('siswa.namaLengkap', 'ASC')->setParameter('id', $siswa);

        $builder
                ->add('siswa', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Siswa', 'label' => 'label.student.entry',
                                'multiple' => false, 'expanded' => false, 'required' => false,
                                'property' => 'namaLengkap', 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'empty_value' => 'label.pilih.siswa'
                        ));

        $querybuilder4 = $em->createQueryBuilder()->select('template')
                ->from('FastSisdikBundle:Templatesms', 'template')->where('template.sekolah = :sekolah')
                ->orderBy('template.nama', 'ASC')->setParameter('sekolah', $sekolah);

        $builder
                ->add('templatesms', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Templatesms',
                                'label' => 'label.sms.template.entry', 'multiple' => false,
                                'expanded' => false, 'required' => true, 'property' => 'optionLabel',
                                'query_builder' => $querybuilder4,
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'empty_value' => 'label.pilih.template.sms'
                        ));

    }

    public function getName() {
        return 'fast_sisdikbundle_kehadiransiswasmstype';
    }
}
