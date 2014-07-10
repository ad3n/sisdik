<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\PendidikanSiswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PendidikanSiswaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('jenjang', 'choice', [
                'choices' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
                'label' => 'label.jenjang',
                'attr' => [
                    'class' => 'medium',
                ],
                'required' => true,
            ])
            ->add('nama', 'text', [
                'label' => 'label.nama.lembaga',
                'attr' => [
                    'class' => 'large',
                ],
                'required' => true,
            ])
            ->add('alamat', 'textarea', [
                'max_length' => 500,
                'label' => 'label.alamat.sekolah',
                'attr' => [
                    'class' => 'large',
                ],
                'required' => true,
            ])
            ->add('ijazahTanggal', 'date', [
                'widget' => 'single_text',
                'label' => 'label.tanggal.ijazah',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date-ijazah small',
                ],
                'required' => false,
            ])
            ->add('ijazahNomor', 'text', [
                'label' => 'label.nomor.ijazah',
                'attr' => [
                    'class' => 'large',
                ],
                'required' => false,
            ])
            ->add('fileUploadIjazah', 'file', [
                'label' => 'label.file.ijazah',
                'required' => false,
            ])
            ->add('tahunmasuk', 'text', [
                'label' => 'label.tahun.masuk',
                'attr' => [
                    'class' => 'tahunmasuk small',
                ],
                'required' => false,
            ])
            ->add('tahunkeluar', 'text', [
                'label' => 'label.tahun.keluar',
                'attr' => [
                    'class' => 'tahunkeluar small',
                ],
                'required' => false,
            ])
            ->add('kelulusanTanggal', 'date', [
                'widget' => 'single_text',
                'label' => 'label.tanggal.kelulusan',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date-kelulusan small',
                ],
                'required' => false,
            ])
            ->add('kelulusanNomor', 'text', [
                'label' => 'label.nomor.kelulusan',
                'attr' => [
                    'class' => 'large',
                ],
                'required' => false,
            ])
            ->add('fileUploadKelulusan', 'file', [
                'label' => 'label.file.kelulusan',
                'required' => false,
            ])
            ->add('keterangan', 'text', [
                'required' => false,
                'label' => 'label.keterangan',
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PendidikanSiswa',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_pendidikansiswatype';
    }
}
