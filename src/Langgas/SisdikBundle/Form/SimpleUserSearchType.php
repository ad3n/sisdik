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
class SimpleUserSearchType extends AbstractType
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
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('searchoption', 'choice', [
                'choices' => $this->buildChoices(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'label' => 'label.searchkey',
                'required' => false,
                'attr' => [
                    'class' => 'medium search-query',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    private function buildChoices()
    {
        $entities = $this->entityManager->getRepository('LanggasSisdikBundle:Sekolah')->findBy([], ['nama' => 'ASC']);

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
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_cariuser';
    }
}
