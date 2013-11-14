<?php

namespace Fast\SisdikBundle\Form\EventListener;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JumlahBayarSubscriber implements EventSubscriberInterface
{
    private $translator;

    public function __construct(Translator $translator) {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents() {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    public function preSubmit(FormEvent $event) {
        $data = $event->getData();

        $form = $event->getForm();
        if (array_key_exists('persenBayar', $data) && $data['persenBayar'] == 1) {
            $form
                    ->add('jumlahBayar', 'number',
                            array(
                                    'constraints' => array(
                                            new Range(
                                                    array(
                                                            'min' => 0, 'max' => 100,
                                                            'minMessage' => $this->translator
                                                                    ->trans('pencarian.persen.minimal',
                                                                            array(), 'validators'),
                                                            'maxMessage' => $this->translator
                                                                    ->trans('pencarian.persen.maksimal',
                                                                            array(), 'validators'),
                                                    ))
                                    ),
                                    'attr' => array(
                                        'class' => 'small', 'placeholder' => 'label.jumlah.bayar'
                                    ), 'label_render' => false, 'required' => false,
                                    'error_bubbling' => true,
                                    'invalid_message' => /** @Ignore */ $this->translator
                                            ->trans('pencarian.persen.tidak.sah', array(), 'validators'),
                            ));
        } else {
            $form
                    ->add('jumlahBayar', 'number',
                            array(
                                    'precision' => 0, 'grouping' => 3,
                                    'attr' => array(
                                        'class' => 'small', 'placeholder' => 'label.jumlah.bayar'
                                    ), 'label_render' => false, 'required' => false,
                                    'error_bubbling' => true,
                                    'invalid_message' => /** @Ignore */ $this->translator
                                            ->trans('pencarian.nominal.tidak.sah', array(), 'validators'),
                            ));

        }
        $form
                ->add('pembandingBayar', 'choice',
                        array(
                                'required' => true,
                                'choices' => array(
                                    '=' => '=', '>' => '>', '<' => '<', '>=' => '≥', '<=' => '≤'
                                ),
                                'attr' => array(
                                    'class' => 'mini pembanding-bayar'
                                ), 'label_render' => false
                        ))
                ->add('persenBayar', 'checkbox',
                        array(
                            'required' => false, 'label_render' => false,
                        ));
    }

}
