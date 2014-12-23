<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaSekali;
use Langgas\SisdikBundle\Entity\DaftarBiayaSekali;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Membentuk label form BiayaSekali
 */
class BiayaSekaliSubscriber implements EventSubscriberInterface
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

        if ($data instanceof DaftarBiayaSekali) {
            $label = $data->getBiayaSekali()->getJenisbiaya()->getNama()
                . ', '
                . number_format($data->getBiayaSekali()->getNominal(), 0, ',', '.')
            ;

            $form
                ->add('terpilih', 'checkbox', [
                    'attr' => [
                        'class' => 'fee-item'
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

        $biayaSekali = $this
            ->entityManager
            ->getRepository('LanggasSisdikBundle:BiayaSekali')
            ->find($data['biayaSekali'])
        ;

        if ($biayaSekali instanceof BiayaSekali) {
            $label = $biayaSekali->getJenisbiaya()->getNama()
                . ', '
                . number_format($biayaSekali->getNominal(), 0, ',', '.')
            ;

            $form = $event->getForm();
            $form
                ->add('terpilih', 'checkbox', [
                    'attr' => [
                        'class' => 'fee-item'
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
