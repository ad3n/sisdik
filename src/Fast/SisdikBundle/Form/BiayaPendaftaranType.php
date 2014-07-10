<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class BiayaPendaftaranType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var int
     */
    private $nominal;

    /**
     * @var string
     */
    private $mode;

    /**
     * @param ContainerInterface $container
     * @param string             $mode
     * @param int                $nominal
     */
    public function __construct(ContainerInterface $container, $mode, $nominal)
    {
        $this->container = $container;
        $this->nominal = $nominal;
        $this->mode = $mode;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;

        $sekolah = $user->getSekolah();

        $em = $this->container
            ->get('doctrine')
            ->getManager()
        ;

        $querybuilder1 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Tahun', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.tahun', 'DESC')
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
                    'class' => 'small',
                ],
                'read_only' => $this->mode == 'edit' ? true : false,
                'horizontal_input_wrapper_class' => 'col-sm-4 col-md-3 col-lg-2',
            ])
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Gelombang', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('gelombang', 'entity', [
                'class' => 'LanggasSisdikBundle:Gelombang',
                'label' => 'label.admissiongroup.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'read_only' => $this->mode == 'edit' ? true : false,
                'horizontal_input_wrapper_class' => 'col-sm-5 col-md-4 col-lg-3',
            ])
        ;

        if ($this->mode == 'edit') {
            $builder
                ->add('nominalSebelumnya', 'hidden', [
                    'required' => false,
                    'data' => $this->nominal
                ])
            ;
        }

        $querybuilder3 = $em
            ->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Jenisbiaya', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('jenisbiaya', 'entity', [
                'class' => 'LanggasSisdikBundle:Jenisbiaya',
                'label' => 'label.fee.type.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder3,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'read_only' => $this->mode == 'edit' ? true : false,
            ]);

        $builder
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

        if ($this->nominal !== null) {
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
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'langgas_sisdikbundle_biayapendaftarantype';
    }
}
