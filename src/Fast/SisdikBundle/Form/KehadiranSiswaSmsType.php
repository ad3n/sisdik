<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaSmsType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Kelas
     */
    private $kelas;

    /**
     * @var string
     */
    private $tanggal;

    /**
     * @var array
     */
    private $entitiesKehadiran;

    /**
     * @param ContainerInterface $container
     * @param Kelas              $kelas
     * @param array              $tanggal
     * @param array              $entitiesKehadiran
     */
    public function __construct(ContainerInterface $container, Kelas $kelas, $tanggal, $entitiesKehadiran)
    {
        $this->container = $container;
        $this->kelas = $kelas;
        $this->tanggal = $tanggal;
        $this->entitiesKehadiran = $entitiesKehadiran;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('statusKehadiran', 'choice', [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                'attr' => [
                    'class' => 'medium',
                ],
                'empty_value' => 'label.pilih.status.kehadiran',
            ])
            ->add('kelas', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'FastSisdikBundle:Kelas',
                'data' => $this->kelas->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'data' => $this->tanggal,
            ])
            ->add('keterangan', 'textarea', [
                'label' => 'label.keterangan',
                'attr' => [
                    'class' => 'xlarge',
                ],
                'required' => false,
                'help_block' => 'Keterangan hanya digunakan untuk izin dan sakit. Dan siswa harus dipilih.',
            ])
        ;

        $siswa = [];
        foreach ($this->entitiesKehadiran as $kehadiran) {
            if ($kehadiran instanceof KehadiranSiswa) {
                $siswa[] = $kehadiran->getSiswa()->getId();
            }
        }

        if (count($siswa) > 0) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('siswa')
                ->from('FastSisdikBundle:Siswa', 'siswa')
                ->where('siswa.id IN (:id)')
                ->orderBy('siswa.namaLengkap', 'ASC')
                ->setParameter('id', $siswa)
            ;
            $builder
                ->add('siswa', 'entity', [
                    'class' => 'FastSisdikBundle:Siswa',
                    'label' => 'label.student.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'property' => 'namaLengkap',
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'xlarge',
                    ],
                    'empty_value' => 'label.pilih.siswa',
                ])
            ;
        }

        $querybuilder4 = $em->createQueryBuilder()
            ->select('template')
            ->from('FastSisdikBundle:Templatesms', 'template')
            ->where('template.sekolah = :sekolah')
            ->orderBy('template.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('templatesms', 'entity', [
                'class' => 'FastSisdikBundle:Templatesms',
                'label' => 'label.sms.template.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder4,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'empty_value' => 'label.pilih.template.sms',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_kehadiransiswasmstype';
    }
}
