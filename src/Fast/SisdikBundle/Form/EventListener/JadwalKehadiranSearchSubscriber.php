<?php
namespace Fast\SisdikBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

/**
 * Membentuk pilihan pencarian perulangan jadwal kehadiran
 */
class JadwalKehadiranSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
}
