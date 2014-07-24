<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $mode;

    /**
     * @param ContainerInterface $container
     * @param string             $mode
     */
    public function __construct(ContainerInterface $container, $mode = "new")
    {
        $this->container = $container;
        $this->mode = $mode;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('namaLengkap', null, [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.name.full',
            ])
            ->add('nomorInduk', null, [
                'label' => 'label.nomor.induk',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('referensi', new EntityHiddenType($em), [
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
            ->add('sekolahAsal', new EntityHiddenType($em), [
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
                'label' => 'label.gender'
            ])
            ->add('file', 'file', [
                'required' => false,
                'label' => 'label.photo',
            ])
            ->add('agama', null, [
                'required' => true,
                'label' => 'label.religion',
                'attr' => [
                    'class' => 'medium',
                ],
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
            ->add('alamat', 'textarea', [
                'label' => 'label.address',
                'attr' => [
                    'class' => 'xlarge',
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
            ->add('keterangan', 'textarea', [
                'label' => 'label.keterangan',
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => false,
            ])
        ;

        if ($this->mode == "new") {
            $builder
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
                ->add('dibuatOleh', new EntityHiddenType($em), [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $user->getId(),
                ])
            ;
        } elseif ($this->mode == "edit") {
            $builder
                ->add('diubahOleh', new EntityHiddenType($em), [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $user->getId(),
                ])
            ;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Siswa',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_siswatype';
    }
}
