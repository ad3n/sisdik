<?php
namespace Langgas\SisdikBundle\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class TransaksiKeuanganSearchType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('dariTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.dari.tanggal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('hinggaTanggal', 'date', [
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.hingga.tanggal.singkat',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'attr' => [
                    'class' => 'medium search-query',
                    'placeholder' => 'label.searchkey',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('pembandingBayar', 'choice', [
                'required' => true,
                'choices' => [
                    '=' => '=',
                    '>' => '>',
                    '<' => '<',
                    '>=' => '≥',
                    '<=' => '≤',
                ],
                'attr' => [
                    'class' => 'mini pembanding-bayar',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('jumlahBayar', 'number', [
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'small',
                    'placeholder' => 'label.jumlah.bayar',
                ],
                'label_render' => false,
                'required' => false,
                'error_bubbling' => true,
                'invalid_message' => /** @Ignore */ $this->container
                    ->get('translator')
                    ->trans('pencarian.nominal.tidak.sah', [], 'validators'),
                'horizontal' => false,
            ])
        ;
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
        return 'searchform';
    }
}
