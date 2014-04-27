<?php
namespace Fast\SisdikBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Membentuk label bidang-bidang form jumlah bayar
 */
class JumlahBayarSubscriber implements EventSubscriberInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
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
                    'horizontal' => true,
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
                    'horizontal' => true,
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
                'horizontal' => true,
            ])
            ->add('persenBayar', 'checkbox', [
                'required' => false,
                'label_render' => false,
                'horizontal' => true,
            ])
        ;
    }
}
