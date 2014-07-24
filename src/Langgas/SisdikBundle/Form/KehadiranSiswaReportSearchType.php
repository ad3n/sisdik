<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class KehadiranSiswaReportSearchType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context"),
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param SecurityContext $securityContext
     * @param EntityManager   $entityManager
     */
    public function __construct(SecurityContext $securityContext, EntityManager $entityManager)
    {
        $this->securityContext = $securityContext;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->securityContext->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->entityManager;

        $builder
            ->add('dariTanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date1 small',
                    'placeholder' => 'dari.tanggal',
                ],
                'required' => false,
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('hinggaTanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date2 small',
                    'placeholder' => 'hingga.tanggal',
                ],
                'required' => true,
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('tingkat')
            ->from('LanggasSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->orderBy('tingkat.kode')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium pilih-tingkat',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('tahunAkademik.aktif = :aktif')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('aktif', true)
        ;
        $builder
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder,
                'attr' => [
                    'class' => 'medium pilih-kelas',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswareportsearch';
    }
}
