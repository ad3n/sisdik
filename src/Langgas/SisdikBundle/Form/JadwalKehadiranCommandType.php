<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class JadwalKehadiranCommandType extends AbstractType
{
    /**
     * @var string|null
     */
    private $requestUri = NULL;

    /**
     * @param string|null $requestUri
     */
    public function __construct($requestUri = NULL)
    {
        $this->requestUri = $requestUri;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('requestUri', 'hidden', [
                'data' => $this->requestUri,
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_jadwalkehadirancommandtype';
    }
}
