<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\StatusKehadiranKepulangan;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KehadiranSiswaSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('tanggal', 'date',
                        array(
                                'label' => 'label.date', 'widget' => 'single_text',
                                'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.date'
                                ), 'required' => true, 'label_render' => false
                        ))
                ->add('searchkey', null,
                        array(
                                'label' => 'label.searchkey', 'required' => false,
                                'attr' => array(
                                        'class' => 'search-query medium',
                                        'placeholder' => 'label.searchkey'
                                ), 'label_render' => false,
                        ));

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Jenjang', 't')->where('t.idsekolah = :idsekolah')
                    ->orderBy('t.kode')->setParameter('idsekolah', $idsekolah);
            $builder
                    ->add('idjenjang', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Jenjang',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'required' => true,
                                    'property' => 'optionLabel', 'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false
                            ));

            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:Kelas', 't')->leftJoin('t.idjenjang', 't2')
                    ->leftJoin('t.idtahun', 't3')->where('t.idsekolah = :idsekolah')
                    ->andWhere('t3.aktif = :aktif')->orderBy('t2.urutan', 'ASC')
                    ->addOrderBy('t.urutan')->setParameter('idsekolah', $idsekolah)
                    ->setParameter('aktif', TRUE);
            $builder
                    ->add('idkelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas',
                                    'label' => 'label.class.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => false,
                                    'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false, 'empty_value' => 'label.allclass'
                            ));

            $status = '';
            foreach (StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja() as $key => $value) {
                $status .= "'" . $value . "',";
            }
            $status = preg_replace('/,$/', '', $status);
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')
                    ->where('t.idsekolah = :idsekolah')->andWhere("t.nama IN ($status)")
                    ->orderBy('t.nama', 'ASC')->setParameter('idsekolah', $idsekolah);
            $alpa = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')
                    ->findBy(
                            array(
                                    'nama' => current(
                                            StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja()),
                                    'idsekolah' => $idsekolah->getId()
                            ));
            $builder
                    ->add('idstatuskehadirankepulangan', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:StatusKehadiranKepulangan',
                                    'label' => 'label.presence.status.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama', 'required' => false,
                                    'query_builder' => $querybuilder, 'label_render' => false,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'preferred_choices' => $alpa // hardcoded because it's not easy to refactor/normalize the database
                                    , 'empty_value' => 'label.presencestatus'
                            ));
        }
    }

    public function getName() {
        return 'fast_sisdikbundle_kehadiransiswasearchtype';
    }

}
