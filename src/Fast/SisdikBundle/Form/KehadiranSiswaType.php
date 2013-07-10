<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class KehadiranSiswaType extends AbstractType
{
    private $container;
    private $buildparam = array();

    public function __construct(ContainerInterface $container, $buildparam = array()) {
        $this->container = $container;
        $this->buildparam = $buildparam;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();
        $em = $this->container->get('doctrine')->getManager();

        // students
        $querybuilder = $em->createQueryBuilder()->select('kehadiran, siswa')
                ->from('FastSisdikBundle:KehadiranSiswa', 'kehadiran')->leftJoin('kehadiran.kelas', 'kelas')
                ->leftJoin('kehadiran.siswa', 'siswa')->where('kelas.sekolah = :sekolah')
                ->orderBy('kelas.kode')->addOrderBy('siswa.namaLengkap')
                ->setParameter('sekolah', $sekolah->getId());

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
                        ->add('kehadiran_' . $entity->getId(), 'choice',
                                array(
                                        'required' => true, 'expanded' => true, 'multiple' => false,
                                        'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                                        'attr' => array(
                                            'class' => 'medium'
                                        ), 'data' => $entity->getStatusKehadiran()
                                ));

            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        // $resolver->setDefaults(array('data_class' => 'Fast\SisdikBundle\Entity\KehadiranSiswa'));
    }

    public function getName() {
        return 'fast_sisdikbundle_kehadiransiswatype';
    }
}
