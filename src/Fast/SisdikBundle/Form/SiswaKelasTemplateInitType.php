<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaKelasTemplateInitType extends AbstractType
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'tahun')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'small',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_siswakelastemplateinittype';
    }
}
