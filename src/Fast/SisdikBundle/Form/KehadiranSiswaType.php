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
        $idsekolah = $user->getIdsekolah();
        $em = $this->container->get('doctrine')->getManager();

        // status
        $status = '';
        foreach (StatusKehadiranKepulanganType::buildNamaStatusKehadiranSaja() as $key => $value) {
            $status .= "'" . $value . "',";
        }
        $status = preg_replace('/,$/', '', $status);
        $querybuilder_status = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:StatusKehadiranKepulangan', 't')
                ->where('t.idsekolah = :idsekolah')->andWhere("t.nama IN ($status)")
                ->orderBy('t.nama', 'ASC')->setParameter('idsekolah', $idsekolah);
        $results = $querybuilder_status->getQuery()->getResult();
        foreach ($results as $result) {
            $choices[$result->getId()] = $result->getNama();
        }

        // students
        $querybuilder = $em->createQueryBuilder()->select('t, t3')
                ->from('FastSisdikBundle:KehadiranSiswa', 't')->leftJoin('t.idkelas', 't2')
                ->leftJoin('t.idsiswa', 't3')->where('t2.idsekolah = :idsekolah')
                ->orderBy('t2.kode')->addOrderBy('t3.namaLengkap')
                ->setParameter('idsekolah', $idsekolah);

        if ($this->buildparam['tanggal'] != '') {
            $querybuilder->andWhere('t.tanggal = :tanggal');
            $querybuilder->setParameter('tanggal', $this->buildparam['tanggal']);
        }
        if ($this->buildparam['searchkey'] != '') {
            $querybuilder
                    ->andWhere("t3.namaLengkap LIKE :searchkey OR t3.nomorInduk LIKE :searchkey");
            $querybuilder->setParameter('searchkey', '%' . $this->buildparam['searchkey'] . '%');
        }
        if ($this->buildparam['idjenjang'] != '') {
            $querybuilder->andWhere("t2.idjenjang = :idjenjang");
            $querybuilder->setParameter('idjenjang', $this->buildparam['idjenjang']);
        }
        if ($this->buildparam['idkelas'] != '') {
            $querybuilder->andWhere("t2.id = :idkelas");
            $querybuilder->setParameter('idkelas', $this->buildparam['idkelas']);
        }
        if ($this->buildparam['idstatuskehadirankepulangan'] != '') {
            $querybuilder->andWhere("t.idstatusKehadiranKepulangan = :idstatuskehadirankepulangan");
            $querybuilder
                    ->setParameter('idstatuskehadirankepulangan',
                            $this->buildparam['idstatuskehadirankepulangan']);
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
                                        'data' => $student->getIdstatusKehadiranKepulangan()
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
