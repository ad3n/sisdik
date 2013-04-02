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
        $sekolah = $user->getSekolah();
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('tanggal', 'date',
                        array(
                                'label' => 'label.date', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy',
                                'attr' => array(
                                    'class' => 'date small', 'placeholder' => 'label.date'
                                ), 'required' => true, 'label_render' => false
                        ))
                ->add('searchkey', null,
                        array(
                                'label' => 'label.searchkey', 'required' => false,
                                'attr' => array(
                                    'class' => 'search-query medium', 'placeholder' => 'label.searchkey'
                                ), 'label_render' => false,
                        ));

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Jenjang', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.kode')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('jenjang', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Jenjang', 'label' => 'label.class.entry',
                                    'multiple' => false, 'expanded' => false, 'required' => true,
                                    'property' => 'optionLabel', 'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false
                            ));

            $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                    ->leftJoin('t.jenjang', 't2')->leftJoin('t.tahunAkademik', 't3')
                    ->where('t.sekolah = :sekolah')->andWhere('t3.aktif = :aktif')
                    ->orderBy('t2.urutan', 'ASC')->addOrderBy('t.urutan')->setParameter('sekolah', $sekolah)
                    ->setParameter('aktif', TRUE);
            $builder
                    ->add('kelas', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                    'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                    'required' => false, 'query_builder' => $querybuilder,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false, 'empty_value' => 'label.allclass'
                            ));

            $status = array();
            foreach (StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja() as $key => $value) {
                $status[] = $value;
            }
            $querybuilder = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')->where('t.sekolah = :sekolah')
                    ->andWhere("t.nama IN (?1)")->orderBy('t.nama', 'ASC')->setParameter('sekolah', $sekolah)
                    ->setParameter(1, $status);
            $alpa = $em->getRepository('FastSisdikBundle:StatusKehadiranKepulangan')
                    ->findBy(
                            array(
                                    'nama' => current(
                                            StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja()),
                                    'sekolah' => $sekolah->getId()
                            ));
            $builder
                    ->add('statuskehadirankepulangan', 'entity',
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
