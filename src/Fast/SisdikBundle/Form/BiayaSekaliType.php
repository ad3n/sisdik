<?php
namespace Fast\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BiayaSekaliType extends AbstractType
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')
            ->getToken()
            ->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('t')
                ->from('FastSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah);
            $builder->add('tahun', 'entity', array(
                'class' => 'FastSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder1,
                'attr' => array(
                    'class' => 'small'
                )
            ));

            $querybuilder3 = $em->createQueryBuilder()
                ->select('t')
                ->from('FastSisdikBundle:Jenisbiaya', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.nama', 'ASC')
                ->setParameter('sekolah', $sekolah);
            $builder->add('jenisbiaya', 'entity', array(
                'class' => 'FastSisdikBundle:Jenisbiaya',
                'label' => 'label.fee.type.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => false,
                'required' => true,
                'query_builder' => $querybuilder3,
                'attr' => array(
                    'class' => 'xlarge'
                )
            ));
        }

        $builder->add('nominal', 'money', array(
            'currency' => 'IDR',
            'required' => true,
            'precision' => 0,
            'grouping' => 3,
            'attr' => array(
                'class' => 'large'
            )
        ))->add('urutan', 'choice', array(
            'choices' => $this->buildOrderChoices(),
            'required' => true,
            'multiple' => false,
            'expanded' => false,
            'attr' => array(
                'class' => 'small'
            )
        ));
    }

    public function buildOrderChoices()
    {
        return array_combine(range(1, 100), range(1, 100));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\BiayaSekali'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_biayasekalitype';
    }
}
