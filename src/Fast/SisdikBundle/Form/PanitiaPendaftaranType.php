<?php

namespace Fast\SisdikBundle\Form;
use Doctrine\Common\Collections\ArrayCollection;
use Fast\SisdikBundle\Entity\Personil;
use Fast\SisdikBundle\Form\PersonilType;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PanitiaPendaftaranType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahunmasuk', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.tahun', 'DESC')
                    ->setParameter('sekolah', $sekolah->getId());
            $builder
                    ->add('tahunmasuk', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahunmasuk',
                                    'label' => 'label.yearentry.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'tahun', 'empty_value' => false,
                                    'required' => true, 'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'small'
                                    )
                            ));
        }

        $builder
                ->add('daftarPersonil', 'collection',
                        array(
                                'type' => new PersonilType(), 'required' => true, 'allow_add' => true,
                                'allow_delete' => true, 'by_reference' => true,
                                'widget_add_btn' => array(
                                        'label' => 'label.committee.add',
                                        'attr' => array(
                                            'class' => 'btn'
                                        ), 'icon' => 'plus-sign',
                                ), 'label' => 'label.committee.list', 'prototype' => true,
                                'options' => array(
                                        'widget_control_group' => true,
                                        'widget_remove_btn' => array(
                                                'label' => 'label.delete',
                                                'attr' => array(
                                                    'class' => 'btn'
                                                ), 'icon' => 'trash',
                                        ), 'label_render' => false,
                                ), 'label_render' => true, 'widget_control_group' => true,
                        ));

        $panitia = $builder->getData()->getDaftarPersonil();
        if ($panitia->count() > 0) {
            $daftarPersonil = new ArrayCollection();
            foreach ($panitia as $personil) {
                if ($personil instanceof Personil) {
                    if ($personil->getId() !== NULL) {
                        $entity = $em->getRepository('FastSisdikBundle:User')->find($personil->getId());
                        $personil->setUser($entity->getName());

                        $daftarPersonil->add($personil);
                    }
                }
            }
            if ($daftarPersonil->count() > 0) {
                $builder->getData()->setDaftarPersonil($daftarPersonil);
            }
        }

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PanitiaPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_panitiapendaftarantype';
    }
}
