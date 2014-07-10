<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $buildparam = [];

    /**
     * @param ContainerInterface $container
     * @param array              $buildparam
     */
    public function __construct(ContainerInterface $container, $buildparam = [])
    {
        $this->container = $container;
        $this->buildparam = $buildparam;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();
        $em = $this->container->get('doctrine')->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('kehadiran, siswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah->getId())
        ;

        if ($this->buildparam['tanggal'] != '') {
            $querybuilder->andWhere('kehadiran.tanggal = :tanggal');
            $querybuilder->setParameter('tanggal', $this->buildparam['tanggal']);
        }
        if ($this->buildparam['searchkey'] != '') {
            $querybuilder->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey");
            $querybuilder->setParameter('searchkey', '%' . $this->buildparam['searchkey'] . '%');
        }
        if ($this->buildparam['tingkat'] != '') {
            $querybuilder->andWhere("kelas.tingkat = :tingkat");
            $querybuilder->setParameter('tingkat', $this->buildparam['tingkat']);
        }
        if ($this->buildparam['kelas'] != '') {
            $querybuilder->andWhere("kelas.id = :kelas");
            $querybuilder->setParameter('kelas', $this->buildparam['kelas']);
        }
        if ($this->buildparam['statusKehadiran'] != '') {
            $querybuilder->andWhere("kehadiran.statusKehadiran = :statusKehadiran");
            $querybuilder->setParameter('statusKehadiran', $this->buildparam['statusKehadiran']);
        }
        $entities = $querybuilder->getQuery()->getResult();

        foreach ($entities as $entity) {
            if (is_object($entity) && $entity instanceof KehadiranSiswa) {
                $builder
                    ->add('kehadiran_' . $entity->getId(), 'choice', [
                        'required' => true,
                        'expanded' => true,
                        'multiple' => false,
                        'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                        'attr' => [
                            'class' => 'medium',
                        ],
                        'data' => $entity->getStatusKehadiran(),
                    ])
                    ->add('kehadiran_keterangan_' . $entity->getId(), 'text', [
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'label.keterangan.kehadiran',
                            'class' => 'keterangan-kehadiran'
                        ],
                        'data' => $entity->getKeteranganStatus(),
                    ])
                ;
            }
        }
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswa';
    }
}
