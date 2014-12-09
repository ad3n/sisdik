<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Langgas\SisdikBundle\Form\EventListener\BiayaPendaftaranSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class DaftarBiayaPendaftaranType extends AbstractType
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
            ->add('biayaPendaftaran', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:BiayaPendaftaran',
            ])
            ->add('nama', 'hidden', [
                'required' => false,
            ])
            ->add('nominal', 'hidden', [
                'required' => true,
            ])
        ;

        $builder->addEventSubscriber(new BiayaPendaftaranSubscriber($this->entityManager));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_daftarbiayapendaftaran';
    }
}
