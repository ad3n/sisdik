<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @FormType
 */
class PembayaranPendaftaranType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @InjectParams({
     *     "tokenStorage" = @Inject("security.token_storage")
     * })
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('daftarBiayaPendaftaran', 'collection', [
                'type' => 'sisdik_daftarbiayapendaftaran',
                'required' => true,
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'options' => [
                    'widget_form_group' => false,
                    'label_render' => false,
                ],
                'label_render' => false,
                'widget_form_group' => false,
            ])
            ->add('adaPotongan', 'checkbox', [
                'label' => 'label.discount',
                'required' => false,
                'attr' => [
                    'class' => 'discount-check',
                ],
                'widget_checkbox_label' => 'widget',
            ])
            ->add('jenisPotongan', 'choice', [
                'label' => 'label.discount',
                'required' => true,
                'choices' => $this->buildJenisPotongan(),
                'expanded' => true,
                'multiple' => false,
                'label_render' => false,
                'attr' => [
                    'class' => 'discount-type',
                ],
            ])
            ->add('persenPotongan', 'percent', [
                'type' => 'integer',
                'required' => false,
                'precision' => 0,
                'attr' => [
                    'class' => 'small percentage-discount',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.discount.percentage',
            ])
            ->add('nominalPotongan', 'money', [
                'currency' => 'IDR',
                'required' => false,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'medium nominal-discount',
                    'autocomplete' => 'off',
                ],
                'label' => 'label.discount.amount',
            ])
            ->add('kategoriPotongan', 'entity', [
                'class' => 'LanggasSisdikBundle:KategoriPotongan',
                'label' => 'label.kategori.potongan',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'placeholder' => 'label.tanpa.kategori.potongan',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('kategoriPotongan')
                        ->where('kategoriPotongan.sekolah = :sekolah')
                        ->orderBy('kategoriPotongan.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
            ])
            ->add('transaksiPembayaranPendaftaran', 'collection', [
                'type' => 'sisdik_transaksipembayaranpendaftaran',
                'by_reference' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.fee.registration.transaction',
                'options' => [
                    'widget_form_group' => false,
                    'label_render' => false,
                ],
                'label_render' => false,
                'allow_add' => true,
                'allow_delete' => false,
            ])
        ;
    }

    public function buildJenisPotongan()
    {
        return [
            'nominal' => 'nominal',
            'persentase' => 'persentase',
        ];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\PembayaranPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_pembayaranpendaftaran';
    }
}
