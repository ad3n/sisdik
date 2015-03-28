<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Membentuk label bidang-bidang form jumlah bayar
 */
class JumlahBayarSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $translator = $this->translator;

        if (array_key_exists('persenBayar', $data) && $data['persenBayar'] == 1) {
            $form
                ->add('jumlahBayar', 'number', [
                    'constraints' => [
                        new Range([
                            'min' => 0,
                            'max' => 100,
                            'minMessage' => $translator->trans('pencarian.persen.minimal', [], 'validators'),
                            'maxMessage' => $translator->trans('pencarian.persen.maksimal', [], 'validators'),
                        ])
                    ],
                    'attr' => [
                        'placeholder' => 'label.jumlah.bayar',
                    ],
                    'label_render' => false,
                    'required' => false,
                    'error_bubbling' => true,
                    'invalid_message' => /** @Ignore */ $translator->trans('pencarian.persen.tidak.sah', [], 'validators'),
                    'horizontal' => false,
                ])
            ;
        } else {
            $form
                ->add('jumlahBayar', 'number', [
                    'precision' => 0,
                    'grouping' => 3,
                    'attr' => [
                        'placeholder' => 'label.jumlah.bayar',
                    ],
                    'label_render' => false,
                    'required' => false,
                    'error_bubbling' => true,
                    'invalid_message' => /** @Ignore */ $translator->trans('pencarian.nominal.tidak.sah', [], 'validators'),
                    'horizontal' => false,
                ])
            ;
        }
        $form
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
                    'class' => 'pembanding-bayar',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('persenBayar', 'checkbox', [
                'label' => '%',
                'required' => false,
                'label_render' => true,
                'horizontal' => false,
            ])
        ;
    }
}
