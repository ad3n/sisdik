<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\DaftarBiayaSekali;
use Langgas\SisdikBundle\Form\EventListener\BiayaSekaliSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class DaftarBiayaSekaliType extends AbstractType
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
        $builder
            ->add('biayaSekali', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:BiayaSekali',
            ])
            ->add('nama', 'hidden', [
                'required' => false,
            ])
            ->add('nominal', 'hidden', [
                'required' => true,
            ])
        ;

        $builder->addEventSubscriber(new BiayaSekaliSubscriber($this->entityManager));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\DaftarBiayaSekali',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_daftarbiayasekali';
    }
}
