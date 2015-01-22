<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
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
class SebarSmsViaAkademikType extends AbstractType
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
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.tingkat',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'property' => 'optionLabel',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tingkat')
                        ->where('tingkat.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan')
                        ->addOrderBy('tingkat.kode')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium pilih-tingkat',
                ],
                'label_render' => true,
                'empty_value' => 'label.seluruh.tingkat',
            ])
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('kelas')
                        ->leftJoin('kelas.tingkat', 'tingkat')
                        ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                        ->where('kelas.sekolah = :sekolah')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->orderBy('tingkat.urutan')
                        ->addOrderBy('kelas.urutan')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('aktif', true)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium pilih-kelas',
                ],
                'label_render' => true,
                'empty_value' => 'label.seluruh.kelas',
            ])
            ->add('filter', 'text', [
                'required' => false,
                'label' => 'label.penyaring.siswa',
                'attr' => [
                    'class' => 'ketik-pilih-tambah saring-siswa-akademik',
                    'placeholder' => 'label.ketik.pilih.nama.siswa',
                ],
            ])
            ->add('keSiswa', 'checkbox', [
                'required' => false,
                'label_render' => true,
                'label' => 'label.kirim.ke.siswa',
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                'help_block' => 'help.penjelasan.kirim.ke.siswa',
            ])
            ->add('pesan', 'textarea', [
                'required' => true,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'label' => 'label.pesan',
                'help_block' => 'help.penjelasan.tag.sebar.sms.akademik',
            ])
            ->add('captcha', 'captcha', [
                'attr' => [
                    'class' => 'medium',
                    'placeholder' => 'help.type.captcha',
                    'autocomplete' => 'off',
                ],
                'as_url' => true,
                'reload' => true,
                'help_block' => 'help.captcha.penjelasan.sebar.sms',
                'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => true,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_sebarsms_via_akademik';
    }
}
