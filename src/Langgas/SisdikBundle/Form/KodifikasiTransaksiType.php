<?php

namespace Langgas\SisdikBundle\Form;

use JMS\DiExtraBundle\Annotation\FormType;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @FormType
 */
class KodifikasiTransaksiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('atributNomorTransaksiBiayaDaftar', 'text', [
                'required' => false,
                'label' => 'label.atribut.nomor.transaksi.biaya.pendaftaran',
                'help_block' => 'penjelasan.atribut.nomor.transaksi.biaya.pendaftaran',
                'attr' => [
                    'placeholder' => 'contoh.atribut.nomor.transaksi.biaya.pendaftaran',
                ],
            ])
            ->add('atributNomorTransaksiBiayaBerulang', 'text', [
                'required' => false,
                'label' => 'label.atribut.nomor.transaksi.biaya.berulang',
                'help_block' => 'penjelasan.atribut.nomor.transaksi.biaya.berulang',
                'attr' => [
                    'placeholder' => 'contoh.atribut.nomor.transaksi.biaya.berulang',
                ],
            ])
            ->add('atributNomorTransaksiBiayaSekali', 'text', [
                'required' => false,
                'label' => 'label.atribut.nomor.transaksi.biaya.sekali',
                'help_block' => 'penjelasan.atribut.nomor.transaksi.biaya.sekali',
                'attr' => [
                    'placeholder' => 'contoh.atribut.nomor.transaksi.biaya.sekali',
                ],
            ])
            ->add('atributNomorTransaksiRestitusi', 'text', [
                'required' => false,
                'label' => 'label.atribut.nomor.transaksi.restitusi',
                'help_block' => 'penjelasan.atribut.nomor.transaksi.restitusi',
                'attr' => [
                    'placeholder' => 'contoh.atribut.nomor.transaksi.restitusi',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Sekolah',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kodifikasitransaksi';
    }
}
