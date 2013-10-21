<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JenisDokumenSiswaType extends AbstractType
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

        $querybuilder1 = $em->createQueryBuilder()
            ->select('tahun')
            ->from('FastSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
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

        $builder->add('sekolah', new EntityHiddenType($em), array(
            'required' => true,
            'class' => 'FastSisdikBundle:Sekolah',
            'data' => $sekolah->getId()
        ))
            ->add('namaDokumen', 'text', array(
            'label' => 'label.jenis.dokumen.siswa',
            'attr' => array(
                'class' => 'xlarge'
            ),
            'required' => true
        ))
            ->add('keterangan', 'textarea', array(
            'attr' => array(
                'class' => 'xlarge'
            ),
            'required' => false
        ))
            ->add('urutan', 'choice', array(
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
            'data_class' => 'Fast\SisdikBundle\Entity\JenisDokumenSiswa'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_jenisdokumensiswatype';
    }
}
