<?php

namespace Fast\SisdikBundle\Form\EventListener;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;
use Fast\SisdikBundle\Entity\DaftarBiayaPendaftaran;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BiayaPendaftaranSubscriber implements EventSubscriberInterface
{
    private $objectManager;

    public function __construct(ObjectManager $objectManager) {
        $this->objectManager = $objectManager;
    }

    public static function getSubscribedEvents() {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    public function preSetData(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        //         $querybuilder = $em->createQueryBuilder()->select('t')
        //                 ->from('FastSisdikBundle:BiayaPendaftaran', 't')->leftJoin('t.jenisbiaya', 't2')
        //                 ->where('t.tahun = :tahun')->setParameter('tahun', $siswa->getTahun()->getId())
        //                 ->andWhere('t.gelombang = :gelombang')
        //                 ->setParameter('gelombang', $siswa->getGelombang()->getId())->orderBy('t.urutan', 'ASC')
        //                 ->addOrderBy('t2.nama', 'ASC');

        //         if (count($this->biayaTerbayar) > 0) {
        //             $querybuilder->andWhere('t.id NOT IN (?1)')->setParameter(1, $this->biayaTerbayar);
        //         }

        //         $results = $querybuilder->getQuery()->getResult();
        //         $availableFees = array();
        //         foreach ($results as $entity) {
        //             if (is_object($entity) && $entity instanceof BiayaPendaftaran) {
        //                 $availableFees[$entity->getId()] = (strlen($entity->getJenisBiaya()->getNama()) > 25 ? substr(
        //                                 $entity->getJenisbiaya()->getNama(), 0, 22) . '...'
        //                         : $entity->getJenisbiaya()->getNama()) . ', '
        //                         . number_format($entity->getNominal(), 0, ',', '.');
        //             }
        //         }

        if ($data instanceof DaftarBiayaPendaftaran) {
            $label = $data->getBiayaPendaftaran()->getJenisbiaya()->getNama() . ', '
                    . number_format($data->getBiayaPendaftaran()->getNominal(), 0, ',', '.');
            $form
                    ->add('terpilih', 'checkbox',
                            array(
                                    'attr' => array(
                                        'class' => 'fee-item'
                                    ), 'label_render' => true, 'label' => /** @Ignore */ $label,
                                    'widget_checkbox_label' => 'widget', 'required' => false,
                            ));
        }
    }

    public function preSubmit(FormEvent $event) {
        $data = $event->getData();

        $biayaPendaftaran = $this->objectManager->getRepository('FastSisdikBundle:BiayaPendaftaran')
                ->find($data['biayaPendaftaran']);

        if ($biayaPendaftaran instanceof BiayaPendaftaran) {
            $label = $biayaPendaftaran->getJenisbiaya()->getNama() . ', '
                    . number_format($biayaPendaftaran->getNominal(), 0, ',', '.');

            $form = $event->getForm();
            $form
                    ->add('terpilih', 'checkbox',
                            array(
                                    'attr' => array(
                                        'class' => 'fee-item'
                                    ), 'label_render' => true, 'label' => /** @Ignore */ $label,
                                    'widget_checkbox_label' => 'widget', 'required' => false,
                            ));
        }
    }

}
