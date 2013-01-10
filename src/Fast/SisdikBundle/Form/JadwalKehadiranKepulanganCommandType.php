<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JadwalKehadiranKepulanganCommandType extends AbstractType
{
    private $requestUri = NULL;

    public function __construct($requestUri = NULL) {
        $this->requestUri = $requestUri;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('requestUri', 'hidden',
                        array(
                            'data' => $this->requestUri,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    }

    public function getName() {
        return 'fast_sisdikbundle_jadwalkehadirankepulangancommandtype';
    }
}
