<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SimpleUserSearchType extends AbstractType
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
        $builder->add('searchoption', 'choice', [
            'choices' => $this->buildChoices(),
            'multiple' => false,
            'expanded' => false,
            'required' => false,
            'attr' => [
                'class' => 'large'
            ],
            'label_render' => false,
            'horizontal' => false,
        ]);
        $builder->add('searchkey', null, [
            'label' => 'label.searchkey',
            'required' => false,
            'attr' => [
                'class' => 'medium search-query',
            ],
            'label_render' => false,
            'horizontal' => false,
        ]);
    }

    private function buildChoices()
    {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository('FastSisdikBundle:Sekolah')->findBy([], ['nama' => 'ASC']);

        $choices = [
            '' => 'label.all',
            'unset' => 'label.unregistered.school',
        ];

        foreach ($entities as $entity) {
            if ($entity instanceof Sekolah) {
                $choices[$entity->getId()] = $entity->getNama();
            }
        }

        return $choices;
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
