<?php

namespace Fast\SisdikBundle\Form\EventListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PenempatanSiswaKelasSubscriber implements EventSubscriberInterface
{
    private $container;
    private $sekolah;

    public function __construct(ContainerInterface $container, Sekolah $sekolah) {
        $this->container = $container;
        $this->sekolah = $sekolah;
    }

    public static function getSubscribedEvents() {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit'
        );
    }

    public function preSetData(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        $em = $this->container->get('doctrine')->getManager();
        $translator = $this->container->get('translator');

        if (is_array($data)) {
            $label = $translator->trans('label.lembar.kerja') . ' ' . ($data['index'] + 1) . ': '
                    . $data['name'];

            $querybuilder1 = $em->createQueryBuilder()->select('tahunAkademik')
                    ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                    ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                    ->addOrderBy('tahunAkademik.nama', 'DESC')->setParameter('sekolah', $this->sekolah);
            $form
                    ->add('tahunAkademik', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:TahunAkademik', 'label' => $label,
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => true, 'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium selectyear' . $data['index']
                                    ),
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('kelas')
                    ->from('FastSisdikBundle:Kelas', 'kelas')->leftJoin('kelas.tingkat', 'tingkat')
                    ->where('kelas.sekolah = :sekolah')->orderBy('tingkat.urutan', 'ASC')
                    ->addOrderBy('kelas.urutan')->setParameter('sekolah', $this->sekolah);
            $form
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas', 'label_render' => false,
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => true, 'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'medium selectclass' . $data['index']
                                    ),
                            ))
                    ->add('index', 'hidden',
                            array(
                                'data' => $data['index']
                            ))
                    ->add('name', 'hidden',
                            array(
                                'data' => $data['name']
                            ));
        }
    }

    public function preSubmit(FormEvent $event) {
        $data = $event->getData();
        $form = $event->getForm();

        $em = $this->container->get('doctrine')->getManager();
        $translator = $this->container->get('translator');

        if (is_array($data)) {
            $label = $translator->trans('label.lembar.kerja') . ' ' . ($data['index'] + 1) . ': '
                    . $data['name'];

            $querybuilder1 = $em->createQueryBuilder()->select('tahunAkademik')
                    ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                    ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                    ->addOrderBy('tahunAkademik.nama', 'DESC')->setParameter('sekolah', $this->sekolah);
            $form
                    ->add('tahunAkademik', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:TahunAkademik', 'label' => $label,
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => true, 'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium selectyear' . $data['index']
                                    ),
                            ));

            $querybuilder2 = $em->createQueryBuilder()->select('kelas')
                    ->from('FastSisdikBundle:Kelas', 'kelas')->leftJoin('kelas.tingkat', 'tingkat')
                    ->where('kelas.sekolah = :sekolah')->orderBy('tingkat.urutan', 'ASC')
                    ->addOrderBy('kelas.urutan')->setParameter('sekolah', $this->sekolah);
            $form
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas', 'label_render' => false,
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => true, 'query_builder' => $querybuilder2,
                                    'attr' => array(
                                        'class' => 'medium selectclass' . $data['index']
                                    ),
                            ))
                    ->add('index', 'hidden',
                            array(
                                'data' => $data['index']
                            ))
                    ->add('name', 'hidden',
                            array(
                                'data' => $data['name']
                            ));
        }
    }
}
