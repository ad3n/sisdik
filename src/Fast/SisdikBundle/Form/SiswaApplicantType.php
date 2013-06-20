<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiswaApplicantType extends AbstractType
{
    private $container;
    private $tahunAktif;
    private $mode;

    public function __construct(ContainerInterface $container, $tahunAktif, $mode = 'new') {
        $this->container = $container;
        $this->tahunAktif = $tahunAktif;
        $this->mode = $mode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();
        $em = $this->container->get('doctrine')->getManager();

        if ($this->mode != 'editregphoto') {
            if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                $builder
                        ->add('sekolah', new EntityHiddenType($em),
                                array(
                                        'required' => true, 'class' => 'FastSisdikBundle:Sekolah',
                                        'data' => $sekolah->getId(),
                                ))
                        ->add('tahun', new EntityHiddenType($em),
                                array(
                                        'required' => true, 'class' => 'FastSisdikBundle:Tahun',
                                        'data' => $this->tahunAktif,
                                ));
            }
        }

        if ($this->mode == 'new') {

            if (is_object($sekolah) && $sekolah instanceof Sekolah) {
                $querybuilder2 = $em->createQueryBuilder()->select('t')
                        ->from('FastSisdikBundle:Gelombang', 't')->where('t.sekolah = :sekolah')
                        ->orderBy('t.urutan', 'ASC')->setParameter('sekolah', $sekolah);
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
            }

            $builder
                    ->add('namaLengkap', null,
                            array(
                                    'required' => true,
                                    'attr' => array(
                                        'class' => 'large'
                                    ), 'label' => 'label.name.full'
                            ))
                    ->add('orangtuaWali', 'collection',
                            array(
                                    'type' => new OrangtuaWaliInitType(), 'by_reference' => false,
                                    'attr' => array(
                                        'class' => 'large'
                                    ), 'label' => 'label.name.parent.or.guardian',
                                    'options' => array(
                                        'widget_control_group' => false, 'label_render' => false,
                                    ), 'label_render' => false, 'allow_add' => true,
                            ))
                    ->add('adaReferensi', 'checkbox',
                            array(
                                    'label' => 'label.ada.referensi', 'required' => false,
                                    'attr' => array(
                                        'class' => 'referensi-check'
                                    ), 'widget_checkbox_label' => 'widget',
                            ))
                    ->add('referensi', new EntityHiddenType($em),
                            array(
                                    'class' => 'FastSisdikBundle:Referensi', 'label_render' => false,
                                    'required' => false,
                                    'attr' => array(
                                        'class' => 'large id-referensi'
                                    ),
                            ))
                    ->add('namaReferensi', 'text',
                            array(
                                    'required' => false,
                                    'attr' => array(
                                            'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                                            'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                                    ), 'label' => 'label.perujuk'
                            ));
        } elseif ($this->mode == 'editregphoto') {
            $builder
                    ->add('fotoPendaftaran', 'hidden',
                            array(
                                    'attr' => array(
                                        'class' => 'foto-pendaftaran'
                                    )
                            ));
        } else {
            if ($this->container->get('security.context')->isGranted('ROLE_KETUA_PANITIA_PSB')) {
                $querybuilder2 = $em->createQueryBuilder()->select('t')
                        ->from('FastSisdikBundle:Gelombang', 't')->where('t.sekolah = :sekolah')
                        ->orderBy('t.urutan', 'ASC')->setParameter('sekolah', $sekolah);
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
            }

            $builder
                    ->add('namaLengkap', null,
                            array(
                                    'required' => true,
                                    'attr' => array(
                                        'class' => 'large'
                                    ), 'label' => 'label.name.full'
                            ))
                    ->add('referensi', new EntityHiddenType($em),
                            array(
                                    'class' => 'FastSisdikBundle:Referensi', 'label_render' => false,
                                    'required' => false,
                                    'attr' => array(
                                        'class' => 'id-referensi'
                                    ),
                            ))
                    ->add('namaReferensi', 'text',
                            array(
                                    'required' => false,
                                    'attr' => array(
                                            'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                                            'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                                    ), 'label' => 'label.perujuk'
                            ))
                    ->add('sekolahAsal', new EntityHiddenType($em),
                            array(
                                    'class' => 'FastSisdikBundle:SekolahAsal', 'label_render' => false,
                                    'required' => false,
                                    'attr' => array(
                                        'class' => 'id-sekolah-asal'
                                    ),
                            ))
                    ->add('namaSekolahAsal', 'text',
                            array(
                                    'required' => false,
                                    'attr' => array(
                                            'class' => 'xlarge nama-sekolah-asal ketik-pilih-tambah',
                                            'placeholder' => 'label.ketik-pilih.atau.ketik-tambah',
                                    ), 'label' => 'label.sekolah.asal'
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
                    ->add('agama', null,
                            array(
                                    'required' => true, 'label' => 'label.religion',
                                    'attr' => array(
                                        'class' => 'medium'
                                    ),
                            ))
                    ->add('alamat', 'textarea',
                            array(
                                    'label' => 'label.address',
                                    'attr' => array(
                                        'class' => 'xlarge'
                                    ), 'required' => true,
                            ))
                    ->add('keterangan', 'textarea',
                            array(
                                    'label' => 'label.keterangan',
                                    'attr' => array(
                                        'class' => 'xlarge'
                                    ), 'required' => false,
                            ))
                    ->add('file', 'file',
                            array(
                                    'required' => false, 'label' => 'label.photo',
                                    'attr' => array(
                                        'class' => 'small'
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
                            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\Siswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswaapplicanttype';
    }
}
