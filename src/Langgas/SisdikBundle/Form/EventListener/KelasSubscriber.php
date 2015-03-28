<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Tingkat;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Menentukan pilihan kelas siswa berdasarkan tingkat kelas
 */
class KelasSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @var integer
     */
    private $bolehKosong;

    /**
     * @param EntityManager $entityManager
     * @param Sekolah       $sekolah
     * @param integer       $bolehKosong
     */
    public function __construct(EntityManager $entityManager, Sekolah $sekolah, $bolehKosong = 0)
    {
        $this->entityManager = $entityManager;
        $this->sekolah = $sekolah;
        $this->bolehKosong = $bolehKosong;
    }

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

        $tingkat = null;
        $daftarKelas = [];
        if (!is_null($data)) {
            $tingkat = $data->getTingkat();
        }

        if ($tingkat instanceof Tingkat) {
            $daftarKelas = $this->entityManager
                ->createQueryBuilder('kelas')
                ->select('kelas')
                ->from('LanggasSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')
                ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                ->where('kelas.sekolah = :sekolah')
                ->andWhere('tahunAkademik.aktif = :aktif')
                ->andWhere('kelas.tingkat = :tingkat')
                ->orderBy('tingkat.urutan')
                ->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $this->sekolah)
                ->setParameter('aktif', true)
                ->setParameter('tingkat', $tingkat)
                ->getQuery()
                ->getResult()
            ;
        }

        $options = [
            'class' => 'LanggasSisdikBundle:Kelas',
            'label' => 'label.class.entry',
            'multiple' => false,
            'expanded' => false,
            'property' => 'nama',
            'required' => false,
            'choices' => $daftarKelas,
            'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
        ];

        if ($this->bolehKosong) {
            $options['placeholder'] = 'label.seluruh.kelas';
        }

        $form->add('kelas', 'entity', $options);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $tingkat = null;
        $daftarKelas = [];
        if (!is_null($data)) {
            $tingkat = $this->entityManager->getRepository('LanggasSisdikBundle:Tingkat')->find($data['tingkat']);
        }

        if ($tingkat instanceof Tingkat) {
            $daftarKelas = $this->entityManager
                ->createQueryBuilder('kelas')
                ->select('kelas')
                ->from('LanggasSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')
                ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
                ->where('kelas.sekolah = :sekolah')
                ->andWhere('tahunAkademik.aktif = :aktif')
                ->andWhere('kelas.tingkat = :tingkat')
                ->orderBy('tingkat.urutan')
                ->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $this->sekolah)
                ->setParameter('aktif', true)
                ->setParameter('tingkat', $tingkat)
                ->getQuery()
                ->getResult()
            ;
        }

        $options = [
            'class' => 'LanggasSisdikBundle:Kelas',
            'label' => 'label.class.entry',
            'multiple' => false,
            'expanded' => false,
            'property' => 'nama',
            'required' => false,
            'choices' => $daftarKelas,
            'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
        ];

        if ($this->bolehKosong) {
            $options['placeholder'] = 'label.seluruh.kelas';
        }

        $form->add('kelas', 'entity', $options);
    }
}
