<?php
namespace Fast\SisdikBundle\Form\EventListener;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Membentuk label bidang form penempatan siswa di suatu kelas
 */
class PenempatanSiswaKelasSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @param ContainerInterface $container
     * @param Sekolah $sekolah
     */
    public function __construct(ContainerInterface $container, Sekolah $sekolah)
    {
        $this->container = $container;
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
        $em = $this->container->get('doctrine')->getManager();
        $translator = $this->container->get('translator');

        if (is_array($data)) {
            $label = $translator->transChoice('label.lembar.kerja')
                . ' '
                . ($data['index'] + 1)
                . ': '
                . $data['name']
            ;

            $qbTahunAkademik = $em
                ->createQueryBuilder()
                ->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')
                ->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('tahunAkademik.nama', 'DESC')
                ->setParameter('sekolah', $this->sekolah)
            ;

            $form
                ->add('tahunAkademik', 'entity', [
                    'class' => 'FastSisdikBundle:TahunAkademik',
                    'label' => /** @Ignore */ $label,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $qbTahunAkademik,
                    'attr' => [
                        'class' => 'medium selectyear' . $data['index'],
                    ],
                ])
            ;

            $qbKelas = $em
                ->createQueryBuilder()
                ->select('kelas')
                ->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')
                ->where('kelas.sekolah = :sekolah')
                ->orderBy('tingkat.urutan', 'ASC')
                ->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $this->sekolah)
            ;

            $form
                ->add('kelas', 'entity', [
                    'class' => 'FastSisdikBundle:Kelas',
                    'label_render' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $qbKelas,
                    'attr' => [
                        'class' => 'medium selectclass' . $data['index'],
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
        $em = $this->container->get('doctrine')->getManager();
        $translator = $this->container->get('translator');

        if (is_array($data)) {
            $label = $translator->trans('label.lembar.kerja')
                . ' '
                . ($data['index'] + 1)
                . ': '
                . $data['name']
            ;

            $qbTahunAkademik = $em
                ->createQueryBuilder()
                ->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')
                ->orderBy('tahunAkademik.urutan', 'DESC')
                ->addOrderBy('tahunAkademik.nama', 'DESC')
                ->setParameter('sekolah', $this->sekolah)
            ;

            $form
                ->add('tahunAkademik', 'entity', [
                    'class' => 'FastSisdikBundle:TahunAkademik',
                    'label' => /** @Ignore */ $label,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $qbTahunAkademik,
                    'attr' => [
                        'class' => 'medium selectyear' . $data['index'],
                    ],
                ])
            ;

            $qbKelas = $em
                ->createQueryBuilder()
                ->select('kelas')
                ->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')
                ->where('kelas.sekolah = :sekolah')
                ->orderBy('tingkat.urutan', 'ASC')
                ->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $this->sekolah)
            ;

            $form
                ->add('kelas', 'entity', [
                    'class' => 'FastSisdikBundle:Kelas',
                    'label_render' => false,
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'nama',
                    'required' => true,
                    'query_builder' => $qbKelas,
                    'attr' => [
                        'class' => 'medium selectclass' . $data['index'],
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
