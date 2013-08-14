<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;

class SiswaType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()->select('tahun')->from('FastSisdikBundle:Tahun', 'tahun')
                ->where('tahun.sekolah = :sekolah')->orderBy('tahun.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'tahun',
                                'empty_value' => false, 'required' => true,
                                'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('gelombang')
                ->from('FastSisdikBundle:Gelombang', 'gelombang')->where('gelombang.sekolah = :sekolah')
                ->orderBy('gelombang.urutan', 'ASC')->setParameter('sekolah', $sekolah);
        $builder
                ->add('gelombang', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Gelombang',
                                'label' => 'label.admissiongroup.entry', 'multiple' => false,
                                'expanded' => false, 'property' => 'nama', 'empty_value' => false,
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ));

        $builder
                ->add('sekolah', new EntityHiddenType($em),
                        array(
                                'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                'data' => $sekolah->getId(),
                        ))
                ->add('namaLengkap', null,
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.name.full'
                        ))
                ->add('nomorInduk', null,
                        array(
                                'label' => 'label.number',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('jenisKelamin', 'choice',
                        array(
                                'required' => true,
                                'choices' => array(
                                    'L' => 'Laki-laki', 'P' => 'Perempuan'
                                ), 'expanded' => true, 'multiple' => false,
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label' => 'label.gender'
                        ))
                ->add('file', 'file',
                        array(
                                'required' => false, 'label' => 'label.photo',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('agama', null,
                        array(
                                'required' => true, 'label' => 'label.religion',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('tempatLahir', null,
                        array(
                                'label' => 'label.birthplace',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('tanggalLahir', 'birthday',
                        array(
                                'label' => 'label.birthday', 'widget' => 'single_text',
                                'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small'
                                ), 'required' => false
                        ))
                ->add('email', 'email',
                        array(
                                'required' => false, 'label' => 'label.email',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('namaPanggilan', null,
                        array(
                                'label' => 'label.nickname',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('kewarganegaraan', null,
                        array(
                                'label' => 'label.nationality',
                                'attr' => array(
                                    'class' => 'medium'
                                )
                        ))
                ->add('anakKe', 'number',
                        array(
                                'label' => 'label.childno', 'required' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('jumlahSaudarakandung', 'number',
                        array(
                                'label' => 'label.brothers.num', 'required' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('jumlahSaudaratiri', 'number',
                        array(
                                'label' => 'label.brothersinlaw.num', 'required' => false,
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('statusOrphan', null,
                        array(
                                'label' => 'label.orphanstatus',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('bahasaSeharihari', null,
                        array(
                                'label' => 'label.dailylanguage',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'label' => 'label.address',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ),
                        ))
                ->add('kodepos', null,
                        array(
                                'label' => 'label.postalcode',
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('telepon', null,
                        array(
                                'label' => 'label.phone',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('ponselSiswa', null,
                        array(
                                'label' => 'label.mobilephone.student',
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ))
                ->add('sekolahTinggaldi', null,
                        array(
                                'label' => 'label.livein.whilestudy',
                                'attr' => array(
                                    'class' => 'large'
                                ),
                        ))
                ->add('jarakTempat', null,
                        array(
                                'label' => 'label.distance.toschool',
                                'attr' => array(
                                    'class' => 'mini'
                                )
                        ))
                ->add('caraKesekolah', null,
                        array(
                                'label' => 'label.how.toschool',
                                'attr' => array(
                                    'class' => 'large'
                                )
                        ))
                ->add('beratbadan', null,
                        array(
                                'label' => 'label.bodyweight',
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('tinggibadan', null,
                        array(
                                'label' => 'label.bodyheight',
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('golongandarah', null,
                        array(
                                'label' => 'label.bloodtype',
                                'attr' => array(
                                    'class' => 'mini'
                                ),
                        ))
                ->add('keterangan', 'textarea',
                        array(
                                'label' => 'label.keterangan',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'required' => true,
                        ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Siswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswatype';
    }
}
