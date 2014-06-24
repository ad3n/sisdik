<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaApplicantType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var integer
     */
    private $tahunAktif;

    /**
     * @var string
     */
    private $mode;

    /**
     * @param ContainerInterface $container
     * @param integer            $tahunAktif
     * @param string             $mode
     */
    public function __construct(ContainerInterface $container, $tahunAktif, $mode = 'new')
    {
        $this->container = $container;
        $this->tahunAktif = $tahunAktif;
        $this->mode = $mode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        if ($this->mode != 'editregphoto') {
            if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                $builder
                    ->add('sekolah', new EntityHiddenType($em), [
                        'required' => true,
                        'class' => 'FastSisdikBundle:Sekolah',
                        'data' => $sekolah->getId(),
                    ])
                    ->add('tahun', new EntityHiddenType($em), [
                        'required' => true,
                        'class' => 'FastSisdikBundle:Tahun',
                        'data' => $this->tahunAktif,
                    ])
                ;
            }
        }

        if ($this->mode == 'new') {
            if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                $querybuilder2 = $em->createQueryBuilder()
                    ->select('gelombang')
                    ->from('FastSisdikBundle:Gelombang', 'gelombang')
                    ->where('gelombang.sekolah = :sekolah')
                    ->orderBy('gelombang.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah)
                ;
                $builder
                    ->add('gelombang', 'entity', [
                        'class' => 'FastSisdikBundle:Gelombang',
                        'label' => 'label.admissiongroup.entry',
                        'multiple' => false,
                        'expanded' => false,
                        'property' => 'nama',
                        'empty_value' => false,
                        'required' => true,
                        'query_builder' => $querybuilder2,
                        'attr' => [
                            'class' => 'medium'
                        ],
                    ])
                ;
            }

            $builder
                ->add('namaLengkap', null, [
                    'required' => true,
                    'attr' => [
                        'class' => 'large',
                    ],
                    'label' => 'label.name.full',
                ])
                ->add('orangtuaWali', 'collection', [
                    'type' => new OrangtuaWaliInitType(),
                    'by_reference' => false,
                    'attr' => [
                        'class' => 'large',
                    ],
                    'label' => 'label.name.parent.or.guardian',
                    'options' => [
                        'widget_form_group' => false,
                        'label_render' => false,
                    ],
                    'label_render' => false,
                    'allow_add' => true,
                ])
                ->add('adaReferensi', 'checkbox', [
                    'label' => 'label.ada.referensi',
                    'required' => false,
                    'attr' => [
                        'class' => 'referensi-check',
                    ],
                    'widget_checkbox_label' => 'widget',
                    'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                ])
                ->add('referensi', new EntityHiddenType($em), [
                    'class' => 'FastSisdikBundle:Referensi',
                    'label_render' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'large id-referensi',
                    ],
                ])
                ->add('namaReferensi', 'text', [
                    'required' => false,
                    'attr' => [
                        'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                        'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                    ],
                    'label' => 'label.perujuk',
                ])
                ->add('dibuatOleh', new EntityHiddenType($em), [
                    'required' => true,
                    'class' => 'FastSisdikBundle:User',
                    'data' => $user->getId(),
                ])
            ;
        } elseif ($this->mode == 'editregphoto') {
            $builder
                ->add('fotoPendaftaran', 'hidden', [
                    'attr' => [
                        'class' => 'foto-pendaftaran',
                    ],
                ])
                ->add('diubahOleh', new EntityHiddenType($em), [
                    'required' => true,
                    'class' => 'FastSisdikBundle:User',
                    'data' => $user->getId(),
                ])
            ;
        } else {
            if ($this->container->get('security.context')->isGranted('ROLE_KETUA_PANITIA_PSB')) {
                $querybuilder2 = $em->createQueryBuilder()
                    ->select('gelombang')
                    ->from('FastSisdikBundle:Gelombang', 'gelombang')
                    ->where('gelombang.sekolah = :sekolah')
                    ->orderBy('gelombang.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah)
                ;
                $builder
                    ->add('gelombang', 'entity', [
                        'class' => 'FastSisdikBundle:Gelombang',
                        'label' => 'label.admissiongroup.entry',
                        'multiple' => false,
                        'expanded' => false,
                        'property' => 'nama',
                        'empty_value' => false,
                        'required' => true,
                        'query_builder' => $querybuilder2,
                        'attr' => [
                            'class' => 'medium',
                        ],
                    ])
                ;
            }

            $builder
                ->add('namaLengkap', null, [
                    'required' => true,
                    'attr' => [
                        'class' => 'large',
                    ],
                    'label' => 'label.name.full',
                ])
                ->add('referensi', new EntityHiddenType($em), [
                    'class' => 'FastSisdikBundle:Referensi',
                    'label_render' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'id-referensi',
                    ],
                ])
                ->add('namaReferensi', 'text', [
                    'required' => false,
                    'attr' => [
                        'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                        'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                    ],
                    'label' => 'label.perujuk',
                ])
                ->add('sekolahAsal', new EntityHiddenType($em), [
                    'class' => 'FastSisdikBundle:SekolahAsal',
                    'label_render' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'id-sekolah-asal',
                    ],
                ])
                ->add('namaSekolahAsal', 'text', [
                    'required' => false,
                    'attr' => [
                        'class' => 'xlarge nama-sekolah-asal ketik-pilih-tambah',
                        'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                    ],
                    'label' => 'label.sekolah.asal',
                ])
                ->add('jenisKelamin', 'choice', [
                    'required' => true,
                    'choices' => [
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'attr' => [
                        'class' => 'medium',
                    ],
                    'label' => 'label.gender',
                ])
                ->add('agama', null, [
                    'required' => true,
                    'label' => 'label.religion',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('alamat', 'textarea', [
                    'label' => 'label.address',
                    'attr' => [
                        'class' => 'xlarge',
                    ],
                    'required' => true,
                ])
                ->add('keterangan', 'textarea', [
                    'label' => 'label.keterangan',
                    'attr' => [
                        'class' => 'xlarge',
                    ],
                    'required' => false,
                ])
                ->add('file', 'file', [
                    'required' => false,
                    'label' => 'label.photo',
                ])
                ->add('tempatLahir', null, [
                    'label' => 'label.birthplace',
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
                ->add('tanggalLahir', 'birthday', [
                    'label' => 'label.birthday',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr' => [
                        'class' => 'date small',
                    ],
                    'required' => false,
                ])
                ->add('email', 'email', [
                    'required' => false,
                    'label' => 'label.email',
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
                ->add('namaPanggilan', null, [
                    'label' => 'label.nickname',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('kewarganegaraan', null, [
                    'label' => 'label.nationality',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('anakKe', 'number', [
                    'label' => 'label.childno',
                    'required' => false,
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('jumlahSaudarakandung', 'number', [
                    'label' => 'label.brothers.num',
                    'required' => false,
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('jumlahSaudaratiri', 'number', [
                    'label' => 'label.brothersinlaw.num',
                    'required' => false,
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('statusOrphan', null, [
                    'label' => 'label.orphanstatus',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('bahasaSeharihari', null, [
                    'label' => 'label.dailylanguage',
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
                ->add('kodepos', null, [
                    'label' => 'label.postalcode',
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('telepon', null, [
                    'label' => 'label.phone',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('ponselSiswa', null, [
                    'label' => 'label.mobilephone.student',
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
                ->add('sekolahTinggaldi', null, [
                    'label' => 'label.livein.whilestudy',
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
                ->add('jarakTempat', null, [
                    'label' => 'label.distance.toschool',
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('caraKesekolah', null, [
                    'label' => 'label.how.toschool',
                    'attr' => [
                        'class' => 'large',
                    ],
                ])
                ->add('beratbadan', null, [
                    'label' => 'label.bodyweight',
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('tinggibadan', null, [
                    'label' => 'label.bodyheight',
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('golongandarah', null, [
                    'label' => 'label.bloodtype',
                    'attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('diubahOleh', new EntityHiddenType($em), [
                    'required' => true,
                    'class' => 'FastSisdikBundle:User',
                    'data' => $user->getId(),
                ])
            ;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Fast\SisdikBundle\Entity\Siswa',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_siswaapplicanttype';
    }
}
