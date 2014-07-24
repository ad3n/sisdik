<?php
namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var int
     */
    private $nominal;

    /**
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context"),
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param SecurityContext $securityContext
     * @param EntityManager   $entityManager
     */
    public function __construct(SecurityContext $securityContext, EntityManager $entityManager)
    {
        $this->securityContext = $securityContext;
        $this->entityManager = $entityManager;

        $this->sekolah = $this->securityContext->getToken()->getUser()->getSekolah();
    }

    /**
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @param int $nominal
     */
    public function setNominal($nominal)
    {
        $this->nominal = $nominal;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setMode($options['mode']);
        $this->setNominal($options['nominal']);

        $querybuilder1 = $this->entityManager
            ->createQueryBuilder()
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $this->sekolah)
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

        $querybuilder2 = $this->entityManager
            ->createQueryBuilder()
            ->select('gelombang')
            ->from('LanggasSisdikBundle:Gelombang', 'gelombang')
            ->where('gelombang.sekolah = :sekolah')
            ->orderBy('gelombang.urutan', 'ASC')
            ->setParameter('sekolah', $this->sekolah)
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

        $querybuilder3 = $this->entityManager
            ->createQueryBuilder()
            ->select('jenisbiaya')
            ->from('LanggasSisdikBundle:Jenisbiaya', 'jenisbiaya')
            ->where('jenisbiaya.sekolah = :sekolah')
            ->orderBy('jenisbiaya.nama', 'ASC')
            ->setParameter('sekolah', $this->sekolah)
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
