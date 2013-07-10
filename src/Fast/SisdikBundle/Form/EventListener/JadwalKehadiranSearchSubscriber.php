<?php

namespace Fast\SisdikBundle\Form\EventListener;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormEvent;

class JadwalKehadiranSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param em EntityManager
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public static function getSubscribedEvents() {
        return array(
            FormEvents::PRE_SUBMIT => 'preSubmit',
        );
    }

    public function preSubmit(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        $form
                ->add('perulangan', 'choice',
                        array(
                                'choices' => JadwalKehadiran::getDaftarPerulangan(),
                                'label' => 'label.selectrepetition', 'multiple' => false,
                                'expanded' => false, 'required' => true,
                                'attr' => array(
                                    'class' => 'medium pilih-perulangan'
                                ), 'label_render' => false
                        ));

        if (array_key_exists('perulangan', $data)) {
            if ($data['perulangan'] == 'a-harian') {
                $form->add('mingguanHariKe', 'hidden');
                $form->add('bulananHariKe', 'hidden');
            } elseif ($data['perulangan'] == 'b-mingguan') {
                $form->add('bulananHariKe', 'hidden');
                $form
                        ->add('mingguanHariKe', 'choice',
                                array(
                                        'choices' => JadwalKehadiran::getNamaHari(), 'multiple' => false,
                                        'expanded' => false, 'required' => true,
                                        'attr' => array(
                                            'class' => 'medium'
                                        ), 'label_render' => false
                                ));
            } elseif ($data['perulangan'] == 'c-bulanan') {
                $form->add('mingguanHariKe', 'hidden');
                $form
                        ->add('bulananHariKe', 'choice',
                                array(
                                        'choices' => JadwalKehadiran::getAngkaHariSebulan(),
                                        'multiple' => false, 'expanded' => false, 'required' => true,
                                        'attr' => array(
                                            'class' => 'medium'
                                        ), 'label_render' => false
                                ));
            }
        }
    }
}
