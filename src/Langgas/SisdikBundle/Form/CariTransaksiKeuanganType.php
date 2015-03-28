<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class CariTransaksiKeuanganType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @InjectParams({
     *     "translator" = @Inject("translator")
     * })
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('nominalTransaksi', 'number', [
                'precision' => 0,
                'grouping' => 3,
                'attr' => [
                    'class' => 'small',
                    'placeholder' => 'label.nominal.transaksi',
                ],
                'label_render' => false,
                'required' => false,
                'error_bubbling' => true,
                'invalid_message' => /** @Ignore */ $this->translator
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
        return 'sisdik_caritransaksi';
    }
}
