<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class EntityHiddenType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @InjectParams({
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new EntityToIdTransformer($this->entityManager, $options['class']);
        $builder->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'class' => null,
                'invalid_message' => 'Entity tak ditemukan.',
            ])
        ;
    }

    public function getParent()
    {
        return 'hidden';
    }

    public function getName()
    {
        return 'sisdik_entityhidden';
    }
}
