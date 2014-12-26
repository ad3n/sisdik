<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\PenempatanSiswaKelasSubscriber;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class PenempatanSiswaKelasKelompokType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context"),
     *     "translator" = @Inject("translator")
     * })
     *
     * @param SecurityContext $securityContext
     * @param Translator      $translator
     */
    public function __construct(SecurityContext $securityContext, Translator $translator)
    {
        $this->securityContext = $securityContext;
        $this->translator = $translator;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder->addEventSubscriber(new PenempatanSiswaKelasSubscriber($this->translator, $sekolah));
    }

    public function getName()
    {
        return 'sisdik_penempatansiswakelaskelompok';
    }
}
