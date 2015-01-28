<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\User;
use Langgas\SisdikBundle\Form\EventListener\SekolahSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\AbstractType;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SiswaType extends AbstractType
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
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->securityContext->getToken()->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder->addEventSubscriber(new SekolahSubscriber($sekolah));

        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahun')
                        ->where('tahun.sekolah = :sekolah')
                        ->orderBy('tahun.tahun', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('penjurusan', 'entity', [
                'class' => 'LanggasSisdikBundle:Penjurusan',
                'label' => 'label.placement.study',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => 'label.tanpa.penjurusan.studi',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('penjurusan')
                        ->where('penjurusan.sekolah = :sekolah')
                        ->orderBy('penjurusan.root', 'ASC')
                        ->addOrderBy('penjurusan.lft', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
            ])
            ->add('sekolah', 'sisdik_entityhidden', [
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

        if ($options['mode'] == "new") {
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
                ->add('dibuatOleh', 'sisdik_entityhidden', [
                    'required' => true,
                    'class' => 'LanggasSisdikBundle:User',
                    'data' => $this->getUser()->getId(),
                ])
            ;
        } elseif ($options['mode'] == "edit") {
            $builder
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
                'mode' => 'new',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_siswa';
    }
}
