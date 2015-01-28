<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Memastikan bahwa entity sekolah yang terpakai
 * sesuai dengan user yang sedang menggunakan form
 */
class SekolahSubscriber implements EventSubscriberInterface
{
    /**
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @param Sekolah $sekolah
     */
    public function __construct($sekolah)
    {
        $this->sekolah = $sekolah;
    }

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
        if (isset($data['sekolah'])) {
            $data['sekolah'] = $this->sekolah->getId();
        }
        $event->setData($data);
    }
}
