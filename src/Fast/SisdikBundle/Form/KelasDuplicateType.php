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
class KelasDuplicateType extends AbstractType
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
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')
                ->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('tahunAkademik.nama', 'DESC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('tahunAkademikSource', 'entity', [
                    'class' => 'FastSisdikBundle:TahunAkademik',
                    'label' => 'label.from',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'medium',
                    ],
                    'label_render' => true,
                ])
                ->add('tahunAkademikTarget', 'entity', [
                    'class' => 'FastSisdikBundle:TahunAkademik',
                    'label' => 'label.to',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'medium',
                    ],
                    'label_render' => true,
                ])
            ;
        }
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
        return 'fast_sisdikbundle_kelasduplicatetype';
    }
}
