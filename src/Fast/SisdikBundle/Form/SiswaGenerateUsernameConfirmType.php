<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaGenerateUsernameConfirmType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param string
     */
    private $data;

    /**
     * @param ContainerInterface $container
     * @param string             $data
     */
    public function __construct(ContainerInterface $container, $data)
    {
        $this->container = $container;
        $this->data = $data;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sessiondata', 'hidden', [
                'data' => $this->data,
            ])
            ->add('confirmlist', 'checkbox', [
                'label' => 'label.generated.valid',
                'required' => true,
                'help_block' => 'help.confirm.generated.username',
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
            ->add('captcha', 'captcha', [
                'attr' => [
                    'class' => 'medium',
                    'placeholder' => 'help.type.captcha',
                    'autocomplete' => 'off',
                ],
                'as_url' => true,
                'reload' => true,
                'help_block' => 'help.captcha.username.explain',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_siswagenerateusernameconfirmtype';
    }
}
