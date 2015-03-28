<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
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
class KepulanganSiswaType extends AbstractType
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
            ->select('kepulangan, siswa')
            ->from('LanggasSisdikBundle:KepulanganSiswa', 'kepulangan')
            ->leftJoin('kepulangan.kelas', 'kelas')
            ->leftJoin('kepulangan.siswa', 'siswa')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('kelas.kode')
            ->addOrderBy('siswa.namaLengkap')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($options['buildparam']['tanggal'] != '') {
            $querybuilder->andWhere('kepulangan.tanggal = :tanggal');
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
            $querybuilder->andWhere("kepulangan.kelas = :kelas");
            $querybuilder->setParameter('kelas', $options['buildparam']['kelas']);
        }
        if ($options['buildparam']['statusKepulangan'] != '') {
            $querybuilder->andWhere("kepulangan.statusKepulangan = :statusKepulangan");
            $querybuilder->setParameter('statusKepulangan', $options['buildparam']['statusKepulangan']);
        }
        $entities = $querybuilder->getQuery()->getResult();

        foreach ($entities as $entity) {
            if (is_object($entity) && $entity instanceof KepulanganSiswa) {
                $builder
                    ->add('kepulangan_' . $entity->getId(), 'choice', [
                        'required' => true,
                        'expanded' => true,
                        'multiple' => false,
                        'choices' => JadwalKepulangan::getDaftarStatusKepulangan(),
                        'attr' => [
                            'class' => 'medium',
                        ],
                        'data' => $entity->getStatusKepulangan(),
                    ])
                    ->add('kepulangan_keterangan_' . $entity->getId(), 'text', [
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'label.keterangan.kepulangan',
                            'class' => 'keterangan-kepulangan'
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
        return 'sisdik_kepulangansiswa';
    }
}
