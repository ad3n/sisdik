<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Langgas\SisdikBundle\Entity\DokumenSiswa;
use Langgas\SisdikBundle\Entity\JenisDokumenSiswa;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Membentuk label bidang form DokumenSiswa
 */
class DokumenFieldSubscriber implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
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

        if ($data instanceof DokumenSiswa) {
            $label = $data->getJenisDokumenSiswa()->getNamaDokumen();

            $form
                ->add('fileUpload', 'file', [
                    'required' => false,
                    'label_render' => true,
                    'label' => /** @Ignore */ $label,
                ])
                ->add('lengkap', 'choice', [
                    'choices' => [
                        1 => 'label.lengkap',
                        0 => 'label.tidak.lengkap'
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true,
                    'label_render' => false,
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

        $jenisDokumen = $this
            ->objectManager
            ->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')
            ->find($data['jenisDokumenSiswa'])
        ;

        if ($jenisDokumen instanceof JenisDokumenSiswa) {
            $label = $jenisDokumen->getNamaDokumen();

            $form = $event->getForm();
            $form
                ->add('fileUpload', 'file', [
                    'required' => false,
                    'label_render' => true,
                    'label' => /** @Ignore */ $label,
                ])
                ->add('lengkap', 'choice', [
                    'choices' => [
                        1 => 'label.lengkap',
                        0 => 'label.tidak.lengkap'
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => true,
                    'label_render' => false,
                ])
            ;
        }
    }
}
