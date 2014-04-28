<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaExportType extends AbstractType
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
            $querybuilder = $em->createQueryBuilder()
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
    }

    public function getName()
    {
        return 'fast_sisdikbundle_siswaexporttype';
    }
}

