<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Form\EventListener\JumlahBayarSubscriber;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class CariLaporanPembayaranPendaftaranType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context"),
     *     "translator" = @Inject("translator")
     * })
     *
     * @param SecurityContext $securityContext
     * @param Translator      $translator
     */
    public function __construct(SecurityContext $securityContext, Translator $translator)
    {
        $this->securityContext = $securityContext;
        $this->translator = $translator;
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

        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => 'label.selectyear',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahun')
                        ->where('tahun.sekolah = :sekolah')
                        ->orderBy('tahun.tahun', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'small pilih-tahun',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('gelombang', 'entity', [
                'class' => 'LanggasSisdikBundle:Gelombang',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => 'label.selectadmissiongroup',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('gelombang')
                        ->where('gelombang.sekolah = :sekolah')
                        ->orderBy('gelombang.urutan', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium pilih-gelombang',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('dariTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.dari.tanggal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('hinggaTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.hingga.tanggal.singkat',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'attr' => [
                    'class' => 'medium search-query',
                    'placeholder' => 'label.searchkey',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('jenisKelamin', 'choice', [
                'required' => false,
                'choices' => [
                    'L' => 'male',
                    'P' => 'female',
                ],
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'empty_value' => 'label.gender.empty.select',
                'horizontal' => false,
            ])
            ->add('sekolahAsal', 'sisdik_entityhidden', [
                'class' => 'LanggasSisdikBundle:SekolahAsal',
                'attr' => [
                    'class' => 'id-sekolah-asal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('namaSekolahAsal', 'text', [
                'attr' => [
                    'class' => 'xlarge nama-sekolah-asal ketik-pilih-tambah',
                    'placeholder' => 'label.sekolah.asal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('referensi', 'sisdik_entityhidden', [
                'class' => 'LanggasSisdikBundle:Referensi',
                'attr' => [
                    'class' => 'large id-referensi',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('namaReferensi', 'text', [
                'attr' => [
                    'class' => 'xlarge nama-referensi ketik-pilih-tambah',
                    'placeholder' => 'label.perujuk',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('adaRestitusi', 'checkbox', [
                'required' => false,
                'attr' => [
                    'class' => 'ada-restitusi',
                ],
                'label_render' => true,
                'label' => 'label.direstitusi',
                'widget_checkbox_label' => 'widget',
                'horizontal' => false,
            ])
        ;

        $builder->addEventSubscriber(new JumlahBayarSubscriber($this->translator));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_caripembayaranpendaftaran';
    }
}
