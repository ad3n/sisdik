<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\PendidikanSiswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PendidikanSiswaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('jenjang', 'choice',
                        array(
                                'choices' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
                                'label' => 'label.jenjang',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'required' => true,
                        ))
                ->add('nama', 'text',
                        array(
                                'label' => 'label.nama.lembaga',
                                'attr' => array(
                                    'class' => 'large'
                                ), 'required' => true,
                        ))
                ->add('alamat', 'textarea',
                        array(
                                'max_length' => 500, 'label' => 'label.alamat.sekolah',
                                'attr' => array(
                                    'class' => 'large'
                                ), 'required' => true,
                        ))
                ->add('ijazahTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'label' => 'label.tanggal.ijazah',
                                'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date-ijazah small'
                                ), 'required' => false,
                        ))
                ->add('ijazahNomor', 'text',
                        array(
                                'label' => 'label.nomor.ijazah',
                                'attr' => array(
                                    'class' => 'large'
                                ), 'required' => false,
                        ))
                ->add('fileUploadIjazah', 'file',
                        array(
                                'label' => 'label.file.ijazah',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'required' => false,
                        ))
                ->add('tahunmasuk', 'text',
                        array(
                                'label' => 'label.tahun.masuk',
                                'attr' => array(
                                    'class' => 'tahunmasuk small'
                                ), 'required' => false,
                        ))
                ->add('tahunkeluar', 'text',
                        array(
                                'label' => 'label.tahun.keluar',
                                'attr' => array(
                                    'class' => 'tahunkeluar small'
                                ), 'required' => false,
                        ))
                ->add('sttbTanggal', 'date',
                        array(
                                'widget' => 'single_text', 'label' => 'label.tanggal.sttb',
                                'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date-sttb small'
                                ), 'required' => false,
                        ))
                ->add('sttbNomor', 'text',
                        array(
                                'label' => 'label.nomor.sttb',
                                'attr' => array(
                                    'class' => 'large'
                                ), 'required' => false,
                        ))
                ->add('fileUploadSttb', 'file',
                        array(
                                'label' => 'label.file.sttb',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'required' => false,
                        ))
                ->add('keterangan', 'text',
                        array(
                                'required' => false, 'label' => 'label.keterangan',
                                'attr' => array(
                                    'class' => 'xlarge'
                                ),
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PendidikanSiswa'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_pendidikansiswatype';
    }
}
