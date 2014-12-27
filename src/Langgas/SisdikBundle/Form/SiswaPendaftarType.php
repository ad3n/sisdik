<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SiswaPendaftarType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context")
     * })
     *
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->securityContext->getToken()->getUser();
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        if ($options['mode'] != 'editregphoto') {
            $builder
                ->add('sekolah', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:Sekolah',
                    'data' => $sekolah->getId(),
                ])
                ->add('tahun', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:Tahun',
                    'data' => $options['tahun_aktif'],
                ])
            ;
        }

        if ($options['mode'] == 'new') {
            $builder
                ->add('gelombang', 'entity', [
                    'class' => 'LanggasSisdikBundle:Gelombang',
                    'label' => 'label.admissiongroup.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'empty_value' => false,
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('gelombang')
                            ->where('gelombang.sekolah = :sekolah')
                            ->orderBy('gelombang.urutan', 'ASC')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium',
                    ],
                ])
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
                ->add('referensi', 'sisdik_entityhidden', [
                    'class' => 'LanggasSisdikBundle:Referensi',
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
                ->add('dibuatOleh', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $this->getUser()->getId(),
                ])
            ;
        } elseif ($options['mode'] == 'editregphoto') {
            $builder
                ->add('fotoPendaftaran', 'hidden', [
                    'attr' => [
                        'class' => 'foto-pendaftaran',
                    ],
                ])
                ->add('diubahOleh', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $this->getUser()->getId(),
                ])
            ;
        } else {
            if ($this->securityContext->isGranted('ROLE_KETUA_PANITIA_PSB')) {
                $builder
                    ->add('gelombang', 'entity', [
                        'class' => 'LanggasSisdikBundle:Gelombang',
                        'label' => 'label.admissiongroup.entry',
                        'multiple' => false,
                        'expanded' => false,
                        'property' => 'nama',
                        'empty_value' => false,
                        'required' => true,
                        'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                            $qb = $repository->createQueryBuilder('gelombang')
                                ->where('gelombang.sekolah = :sekolah')
                                ->orderBy('gelombang.urutan', 'ASC')
                                ->setParameter('sekolah', $sekolah)
                            ;

                            return $qb;
                        },
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
                ->add('referensi', 'sisdik_entityhidden', [
                    'class' => 'LanggasSisdikBundle:Referensi',
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
                ->add('sekolahAsal', 'sisdik_entityhidden', [
                    'class' => 'LanggasSisdikBundle:SekolahAsal',
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
                ->add('diubahOleh', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $this->getUser()->getId(),
                ])
            ;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Siswa',
                'tahun_aktif' => null,
                'mode' => 'new',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_siswapendaftar';
    }
}
