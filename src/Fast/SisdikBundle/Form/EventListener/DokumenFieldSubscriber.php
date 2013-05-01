<?php

namespace Fast\SisdikBundle\Form\EventListener;
use Fast\SisdikBundle\Entity\DokumenSiswa;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DokumenFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents() {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_BIND => 'preBind'
        );
    }

    public function preSetData(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data instanceof DokumenSiswa) {
            $label = $data->getJenisDokumenSiswa()->getNamaDokumen();
            $form
                    ->add('fileUpload', 'file',
                            array(
                                'required' => false, 'label_render' => true, 'label' => $label
                            ))
                    ->add('lengkap', 'choice',
                            array(
                                    'choices' => array(
                                        1 => 'label.lengkap', 0 => 'label.tidak.lengkap'
                                    ), 'expanded' => true, 'multiple' => false, 'required' => true,
                                    'label_render' => false,
                            ));
        }
    }

    public function preBind(FormEvent $event) {
        $data = $event->getData();

        $form = $event->getForm();
        $form
                ->add('fileUpload', 'file',
                        array(
                            'required' => false, 'label_render' => true, 'label' => "Fixed"
                        ))
                ->add('lengkap', 'choice',
                        array(
                                'choices' => array(
                                    1 => 'label.lengkap', 0 => 'label.tidak.lengkap'
                                ), 'expanded' => true, 'multiple' => false, 'required' => true,
                                'label_render' => false,
                        ));
    }

}
