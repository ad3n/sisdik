<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\StatusKehadiranKepulangan;
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

        // status
        $status = '';
        foreach (StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja() as $key => $value) {
            $status .= "'" . $value . "',";
        }
        $status = preg_replace('/,$/', '', $status);
        $querybuilder_status = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')
                ->where('t.sekolah = :sekolah')->andWhere("t.nama IN ($status)")
                ->orderBy('t.nama', 'ASC')->setParameter('sekolah', $sekolah);
        $results = $querybuilder_status->getQuery()->getResult();
        foreach ($results as $result) {
            $choices[$result->getId()] = $result->getNama();
        }

        // students
        $querybuilder = $em->createQueryBuilder()->select('t, t3')
                ->from('FastSisdikBundle:KehadiranSiswa', 't')->leftJoin('t.kelas', 't2')
                ->leftJoin('t.siswa', 't3')->where('t2.sekolah = :sekolah')
                ->orderBy('t2.kode')->addOrderBy('t3.namaLengkap')
                ->setParameter('sekolah', $sekolah);

        if ($this->buildparam['tanggal'] != '') {
            $querybuilder->andWhere('t.tanggal = :tanggal');
            $querybuilder->setParameter('tanggal', $this->buildparam['tanggal']);
        }
        if ($this->buildparam['searchkey'] != '') {
            $querybuilder
                    ->andWhere("t3.namaLengkap LIKE :searchkey OR t3.nomorInduk LIKE :searchkey");
            $querybuilder->setParameter('searchkey', '%' . $this->buildparam['searchkey'] . '%');
        }
        if ($this->buildparam['jenjang'] != '') {
            $querybuilder->andWhere("t2.jenjang = :jenjang");
            $querybuilder->setParameter('jenjang', $this->buildparam['jenjang']);
        }
        if ($this->buildparam['kelas'] != '') {
            $querybuilder->andWhere("t2. = :kelas");
            $querybuilder->setParameter('kelas', $this->buildparam['kelas']);
        }
        if ($this->buildparam['statuskehadirankepulangan'] != '') {
            $querybuilder->andWhere("t.statusKehadiranKepulangan = :statuskehadirankepulangan");
            $querybuilder
                    ->setParameter('statuskehadirankepulangan',
                            $this->buildparam['statuskehadirankepulangan']);
        }
        $entities = $querybuilder->getQuery()->getResult();

        foreach ($entities as $student) {
            if (is_object($student) && $student instanceof KehadiranSiswa) {
                $builder
                        ->add('kehadirankepulangan_' . $student->getId(), 'choice',
                                array(
                                        'required' => true, 'expanded' => true,
                                        'multiple' => false, 'choices' => $choices,
                                        'attr' => array(
                                            'class' => 'medium'
                                        ),
                                        'data' => $student->getStatusKehadiranKepulangan()
                                                ->getId()
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
