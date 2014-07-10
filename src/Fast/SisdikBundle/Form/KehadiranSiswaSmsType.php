<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
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
                'class' => 'LanggasSisdikBundle:Kelas',
                'data' => $this->kelas->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'data' => $this->tanggal,
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
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->where('siswa.id IN (:id)')
                ->orderBy('siswa.namaLengkap', 'ASC')
                ->setParameter('id', $siswa)
            ;
            $builder
                ->add('siswa', 'entity', [
                    'class' => 'LanggasSisdikBundle:Siswa',
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
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_kehadiransiswasmstype';
    }
}
