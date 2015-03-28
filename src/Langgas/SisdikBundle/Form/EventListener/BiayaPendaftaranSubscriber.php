<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Membentuk label form BiayaPendaftaran.
 */
class BiayaPendaftaranSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data instanceof DaftarBiayaPendaftaran) {
            $label = $data->getBiayaPendaftaran()->getJenisbiaya()->getNama()
                .', '
                .number_format($data->getBiayaPendaftaran()->getNominal(), 0, ',', '.')
            ;

            $form
                ->add('terpilih', 'checkbox', [
                    'attr' => [
                        'class' => 'fee-item',
                    ],
                    'label_render' => true,
                    'label' => /** @Ignore */ $label,
                    'widget_checkbox_label' => 'widget',
                    'required' => false,
                ])
            ;
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        $biayaPendaftaran = $this
            ->entityManager
            ->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
            ->find($data['biayaPendaftaran'])
        ;

        if ($biayaPendaftaran instanceof BiayaPendaftaran) {
            $label = $biayaPendaftaran->getJenisbiaya()->getNama()
                .', '
                .number_format($biayaPendaftaran->getNominal(), 0, ',', '.')
            ;

            $form = $event->getForm();
            $form
                ->add('terpilih', 'checkbox', [
                    'attr' => [
                        'class' => 'fee-item',
                    ],
                    'label_render' => true,
                    'label' => /** @Ignore */ $label,
                    'widget_checkbox_label' => 'widget',
                    'required' => false,
                ])
            ;
        }
    }
}
