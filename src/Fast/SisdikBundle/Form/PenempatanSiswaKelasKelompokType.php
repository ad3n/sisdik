<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\PenempatanSiswaKelasSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PenempatanSiswaKelasKelompokType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $builder->addEventSubscriber(new PenempatanSiswaKelasSubscriber($this->container, $sekolah));
    }

    public function getName() {
        return 'fast_sisdikbundle_penempatansiswakelaskelompoktype';
    }
}
