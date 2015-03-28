<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class KehadiranSiswaType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @InjectParams({
     *     "tokenStorage" = @Inject("security.token_storage"),
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManager   $entityManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $querybuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('kehadiran, siswa')
            ->from('LanggasSisdikBundle:KehadiranSiswa', 'kehadiran')
            ->leftJoin('kehadiran.kelas', 'kelas')
            ->leftJoin('kehadiran.siswa', 'siswa')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($options['buildparam']['tanggal'] != '') {
            $querybuilder->andWhere('kehadiran.tanggal = :tanggal');
            $querybuilder->setParameter('tanggal', $options['buildparam']['tanggal']);
        }
        if ($options['buildparam']['searchkey'] != '') {
            $querybuilder->andWhere("siswa.namaLengkap LIKE :searchkey OR siswa.nomorInduk LIKE :searchkey OR siswa.nomorIndukSistem = :searchkey2");
            $querybuilder->setParameter('searchkey', '%' . $options['buildparam']['searchkey'] . '%');
            $querybuilder->setParameter('searchkey2', $options['buildparam']['searchkey']);
        }
        if ($options['buildparam']['tingkat'] != '') {
            $querybuilder->andWhere("kelas.tingkat = :tingkat");
            $querybuilder->setParameter('tingkat', $options['buildparam']['tingkat']);
        }
        if ($options['buildparam']['kelas'] != '') {
            $querybuilder->andWhere("kehadiran.kelas = :kelas");
            $querybuilder->setParameter('kelas', $options['buildparam']['kelas']);
        }
        if ($options['buildparam']['statusKehadiran'] != '') {
            $querybuilder->andWhere("kehadiran.statusKehadiran = :statusKehadiran");
            $querybuilder->setParameter('statusKehadiran', $options['buildparam']['statusKehadiran']);
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'buildparam' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswa';
    }
}
