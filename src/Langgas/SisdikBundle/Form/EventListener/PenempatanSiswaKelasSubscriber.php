<?php

namespace Langgas\SisdikBundle\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Membentuk label bidang form penempatan siswa di suatu kelas
 */
class PenempatanSiswaKelasSubscriber implements EventSubscriberInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @param Translator $translator
     * @param Sekolah    $sekolah
     */
    public function __construct(Translator $translator, Sekolah $sekolah)
    {
        $this->translator = $translator;
        $this->sekolah = $sekolah;
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

        $translator = $this->translator;
        $sekolah = $this->sekolah;

        if (is_array($data)) {
            $label = $translator->trans('label.lembar.kerja')
                .' '
                .($data['index'] + 1)
                .': '
                .$data['name']
            ;

            $form
                ->add('tahunAkademik', 'entity', [
                    'class' => 'LanggasSisdikBundle:TahunAkademik',
                    'label' =>/** @Ignore */ $label,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('tahunAkademik')
                            ->where('tahunAkademik.sekolah = :sekolah')
                            ->orderBy('tahunAkademik.urutan', 'DESC')
                            ->addOrderBy('tahunAkademik.nama', 'DESC')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium selectyear'.$data['index'],
                    ],
                ])
                ->add('kelas', 'entity', [
                    'class' => 'LanggasSisdikBundle:Kelas',
                    'label_render' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('kelas')
                            ->leftJoin('kelas.tingkat', 'tingkat')
                            ->where('kelas.sekolah = :sekolah')
                            ->orderBy('tingkat.urutan', 'ASC')
                            ->addOrderBy('kelas.urutan')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium selectclass'.$data['index'],
                    ],
                ])
                ->add('index', 'hidden', [
                    'data' => $data['index'],
                ])
                ->add('name', 'hidden', [
                    'data' => $data['name'],
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
        $form = $event->getForm();

        $translator = $this->translator;
        $sekolah = $this->sekolah;

        if (is_array($data)) {
            $label = $translator->trans('label.lembar.kerja')
                .' '
                .($data['index'] + 1)
                .': '
                .$data['name']
            ;

            $form
                ->add('tahunAkademik', 'entity', [
                    'class' => 'LanggasSisdikBundle:TahunAkademik',
                    'label' =>/** @Ignore */ $label,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('tahunAkademik')
                            ->where('tahunAkademik.sekolah = :sekolah')
                            ->orderBy('tahunAkademik.urutan', 'DESC')
                            ->addOrderBy('tahunAkademik.nama', 'DESC')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium selectyear'.$data['index'],
                    ],
                ])
                ->add('kelas', 'entity', [
                    'class' => 'LanggasSisdikBundle:Kelas',
                    'label_render' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                        $qb = $repository->createQueryBuilder('kelas')
                            ->leftJoin('kelas.tingkat', 'tingkat')
                            ->where('kelas.sekolah = :sekolah')
                            ->orderBy('tingkat.urutan', 'ASC')
                            ->addOrderBy('kelas.urutan')
                            ->setParameter('sekolah', $sekolah)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'medium selectclass'.$data['index'],
                    ],
                ])
                ->add('index', 'hidden', [
                    'data' => $data['index'],
                ])
                ->add('name', 'hidden', [
                    'data' => $data['name'],
                ])
            ;
        }
    }
}
