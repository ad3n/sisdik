<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SiswaApplicantSearchType extends AbstractType
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
                ->from('FastSisdikBundle:Gelombang', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.urutan', 'ASC')
                ->setParameter('sekolah', $sekolah->getId())
            ;
            $builder
                ->add('gelombang', 'entity', [
                    'class' => 'FastSisdikBundle:Gelombang',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'empty_value' => 'label.selectadmissiongroup',
                    'required' => false,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'medium',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        }

        $builder->add('searchkey', null, [
            'required' => false,
            'attr' => [
                'class' => 'medium search-query',
                'placeholder' => 'label.searchkey',
            ],
            'label_render' => false,
            'horizontal' => false,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false
        ]);
    }

    public function getName()
    {
        return 'searchform';
    }
}
