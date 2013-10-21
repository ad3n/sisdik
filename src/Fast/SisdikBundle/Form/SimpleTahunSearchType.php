<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SimpleTahunSearchType extends AbstractType
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
                'empty_value' => 'label.selectyear',
                'required' => false,
                'query_builder' => $querybuilder1,
                'attr' => array(
                    'class' => 'small'
                ),
                'label_render' => false
            ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false
        ));
    }

    public function getName()
    {
        return 'searchform';
    }
}
