<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Form\EventListener\DokumenFieldSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class DokumenSiswaType extends AbstractType
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
            ->add('jenisDokumenSiswa', 'sisdik_entityhidden', [
                'class' => 'LanggasSisdikBundle:JenisDokumenSiswa',
                'label_render' => false,
            ])
            ->add('siswa', 'sisdik_entityhidden', [
                'class' => 'LanggasSisdikBundle:Siswa',
                'label_render' => false,
            ])
        ;

        $builder->addEventSubscriber(new DokumenFieldSubscriber($this->entityManager));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\DokumenSiswa',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_dokumensiswa';
    }
}
