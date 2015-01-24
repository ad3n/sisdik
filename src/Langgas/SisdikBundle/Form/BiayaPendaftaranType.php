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
class BiayaPendaftaranType extends AbstractType
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
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
                    'class' => 'small',
                ],
                'read_only' => $options['mode'] == 'edit' ? true : false,
                'disabled' => $options['mode'] == 'edit' ? true : false,
                'horizontal_input_wrapper_class' => 'col-sm-4 col-md-3 col-lg-2',
            ])
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
                    'class' => 'xlarge',
                ],
                'read_only' => $options['mode'] == 'edit' ? true : false,
                'disabled' => $options['mode'] == 'edit' ? true : false,
                'horizontal_input_wrapper_class' => 'col-sm-5 col-md-4 col-lg-3',
            ])
        ;

        if ($options['mode'] == 'edit') {
            $builder
                ->add('nominalSebelumnya', 'hidden', [
                    'required' => false,
                    'data' => $options['nominal'],
                ])
            ;
        }

        $builder
            ->add('jenisbiaya', 'entity', [
                'class' => 'LanggasSisdikBundle:Jenisbiaya',
                'label' => 'label.fee.type.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('jenisbiaya')
                        ->where('jenisbiaya.sekolah = :sekolah')
                        ->orderBy('jenisbiaya.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
                'read_only' => $options['mode'] == 'edit' ? true : false,
                'disabled' => $options['mode'] == 'edit' ? true : false,
            ])
            ->add('nominal', 'money', [
                'currency' => 'IDR',
                'required' => true,
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'large',
                ],
                'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
            ])
            ->add('urutan', 'choice', [
                'choices' => $this->buildOrderChoices(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'small',
                ],
                'horizontal_input_wrapper_class' => 'col-sm-3 col-md-2 col-lg-1',
            ])
        ;

        if ($options['nominal'] !== null) {
            $builder
                ->add('captcha', 'captcha', [
                    'attr' => [
                        'class' => 'medium',
                        'placeholder' => 'help.type.captcha',
                        'autocomplete' => 'off',
                    ],
                    'as_url' => true,
                    'reload' => true,
                    'help_block' => 'help.captcha.penjelasan.ubah.biaya',
                    'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
                ])
            ;
        }
    }

    /**
     * @return array
     */
    public function buildOrderChoices()
    {
        return array_combine(range(1, 100), range(1, 100));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\BiayaPendaftaran',
                'mode' => 'new',
                'nominal' => null,
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sisdik_biayapendaftaran';
    }
}
