<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * Membentuk pilihan pencarian perulangan jadwal kehadiran dan kepulangan.
 *
 * @FormType
 */
class JadwalSearchSubscriber implements EventSubscriberInterface
{
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

        $form
            ->add('perulangan', 'choice', [
                'choices' => JadwalKehadiran::getDaftarPerulangan(),
                'label' => 'label.selectrepetition',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'medium pilih-perulangan',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        if (array_key_exists('perulangan', $data)) {
            if ($data['perulangan'] == 'a-harian') {
                $form->add('mingguanHariKe', 'hidden');
                $form->add('bulananHariKe', 'hidden');
            } elseif ($data['perulangan'] == 'b-mingguan') {
                $form->add('bulananHariKe', 'hidden');
                $form
                    ->add('mingguanHariKe', 'choice', [
                        'choices' => JadwalKehadiran::getNamaHari(),
                        'multiple' => false,
                        'expanded' => false,
                        'required' => true,
                        'attr' => [
                            'class' => 'medium',
                        ],
                        'label_render' => false,
                        'horizontal' => false,
                    ])
                ;
            } elseif ($data['perulangan'] == 'c-bulanan') {
                $form->add('mingguanHariKe', 'hidden');
                $form
                    ->add('bulananHariKe', 'choice', [
                        'choices' => JadwalKehadiran::getAngkaHariSebulan(),
                        'multiple' => false,
                        'expanded' => false,
                        'required' => true,
                        'attr' => [
                            'class' => 'medium',
                        ],
                        'label_render' => false,
                        'horizontal' => false,
                    ])
                ;
            }
        }
    }

    public function getName()
    {
        return 'sisdik_jadwalsubscriber';
    }
}
