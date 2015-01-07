<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\TahunAkademik;
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
class WaliKelasType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('tahunAkademik', 'entity', [
                'class' => 'LanggasSisdikBundle:TahunAkademik',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahunAkademik')
                        ->where('tahunAkademik.sekolah = :sekolah')
                        ->orderBy('tahunAkademik.urutan', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium selectyear',
                ],
            ])
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('kelas')
                        ->leftJoin('kelas.tingkat', 'tingkat')
                        ->where('kelas.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan', 'ASC')
                        ->addOrderBy('kelas.urutan')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'large selectclass',
                ],
            ])
            ->add('user', 'sisdik_entityhidden', [
                'class' => 'LanggasSisdikBundle:User',
                'label_render' => false,
                'required' => true,
                'attr' => [
                    'class' => 'large id-user',
                ],
            ])
            ->add('namaUser', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'xlarge nama-user ketik-pilih-tambah',
                    'placeholder' => 'label.ketik-pilih',
                ],
                'label' => 'label.user.wali.kelas',
            ])
            ->add('keterangan', 'text', [
                'required' => false,
            ])
            ->add('kirimIkhtisarKehadiran', 'checkbox', [
                'label' => 'label.kirim.sms.ringkasan.kehadiran',
                'required' => false,
                'label_render' => true,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
            ->add('jadwalKirimIkhtisarKehadiran', 'choice', [
                'choices' => $this->buatPilihanMenit(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'mini',
                ],
                'label' => 'label.jadwal.kirim.sms.ringkasan.kehadiran',
                'help_block' => 'help.jadwal.kirim.sms.ringkasan.kehadiran',
            ])
            ->add('templatesmsIkhtisarKehadiran', 'entity', [
                'class' => 'LanggasSisdikBundle:Templatesms',
                'label' => 'label.sms.template.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'property' => 'optionLabel',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('template')
                        ->where('template.sekolah = :sekolah')
                        ->orderBy('template.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
                'empty_value' => 'label.pilih.template.sms',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\WaliKelas',
            ])
        ;
    }

    private function buatPilihanMenit()
    {
        return array_combine([
            -60, -30, -20, -15, -10, -5, 0, 5, 10, 15, 20, 30, 60
        ], [
            -60, -30, -20, -15, -10, -5, 0, 5, 10, 15, 20, 30, 60
        ]);
    }

    public function getName()
    {
        return 'sisdik_walikelas';
    }
}
