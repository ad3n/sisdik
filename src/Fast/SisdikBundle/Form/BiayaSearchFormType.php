<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class BiayaSearchFormType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('t')
                ->from('FastSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('tahun', 'entity', [
                    'class' => 'FastSisdikBundle:Tahun',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'tahun',
                    'empty_value' => 'label.selectyear',
                    'required' => false,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'small'
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;

            $querybuilder2 = $em->createQueryBuilder()
                ->select('t')
                ->from('FastSisdikBundle:Gelombang', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('gelombang', 'entity', [
                    'class' => 'FastSisdikBundle:Gelombang',
                    'label' => 'label.admissiongroup.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'empty_value' => 'label.selectadmissiongroup',
                    'required' => false,
                    'query_builder' => $querybuilder2,
                    'attr' => [
                        'class' => 'medium'
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        }

        $builder
            ->add('jenisbiaya', null, [
                'label' => 'label.fee.type.entry',
                'required' => false,
                'attr' => [
                    'class' => 'search-query small',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getName()
    {
        return 'searchform';
    }
}
