<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JadwalKehadiranKepulanganDuplicateType extends AbstractType
{
    private $container;
    private $sekolahSrc;
    private $tahunAkademikSrc = NULL;
    private $kelasSrc = NULL;
    private $perulanganSrc = NULL;
    private $requestUri = NULL;
    private $mingguanHariKeSrc = NULL;
    private $bulananHariKeSrc = NULL;

    public function __construct(ContainerInterface $container, $sekolah, $tahunAkademik = NULL, $kelas = NULL,
            $perulangan = NULL, $requestUri = NULL, $mingguanHariKe = NULL, $bulananHariKe = NULL) {
        $this->container = $container;
        $this->sekolahSrc = $sekolah;
        $this->tahunAkademikSrc = $tahunAkademik;
        $this->kelasSrc = $kelas;
        $this->perulanganSrc = $perulangan;
        $this->requestUri = $requestUri;
        $this->mingguanHariKeSrc = $mingguanHariKe;
        $this->bulananHariKeSrc = $bulananHariKe;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();

        $builder
                ->add('sekolahSrc', 'hidden',
                        array(
                            'data' => $this->sekolahSrc,
                        ))
                ->add('tahunAkademikSrc', 'hidden',
                        array(
                            'data' => $this->tahunAkademikSrc,
                        ))
                ->add('kelasSrc', 'hidden',
                        array(
                            'data' => $this->kelasSrc,
                        ))
                ->add('perulanganSrc', 'hidden',
                        array(
                            'data' => $this->perulanganSrc,
                        ))
                ->add('requestUri', 'hidden',
                        array(
                            'data' => $this->requestUri,
                        ))
                ->add('mingguanHariKeSrc', 'hidden',
                        array(
                            'data' => $this->mingguanHariKeSrc,
                        ))
                ->add('bulananHariKeSrc', 'hidden',
                        array(
                            'data' => $this->bulananHariKeSrc,
                        ));

        $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:TahunAkademik', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.urutan', 'DESC')
                ->setParameter('sekolah', $this->sekolahSrc);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium selectyearduplicate'
                                ), 'label_render' => false
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.tingkat', 't2')->where('t.sekolah = :sekolah')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('sekolah', $this->sekolahSrc);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium selectclassduplicate'
                                ), 'label_render' => false
                        ));

        $builder
                ->add('perulangan', 'choice',
                        array(
                                'choices' => array(
                                    'harian' => 'harian', 'mingguan' => 'mingguan', 'bulanan' => 'bulanan'
                                ), 'label' => 'label.selectrepetition', 'multiple' => false,
                                'expanded' => false, 'required' => true,
                                'attr' => array(
                                    'class' => 'small'
                                ), 'label_render' => false
                        ))
                ->add('mingguanHariKe', 'choice',
                        array(
                                'choices' => $this->buildDayNames(), 'multiple' => false,
                                'expanded' => false, 'required' => false,
                                'empty_value' => 'label.selectweekday',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false
                        ))
                ->add('bulananHariKe', 'choice',
                        array(
                                'choices' => $this->buildDays(), 'multiple' => false, 'expanded' => false,
                                'required' => false, 'empty_value' => 'label.selectmonthday',
                                'attr' => array(
                                    'class' => 'medium'
                                ), 'label_render' => false
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_jadwalkehadirankepulanganduplicatetype';
    }

    public function buildDayNames() {
        return array(
                0 => 'label.sunday', 'label.monday', 'label.tuesday', 'label.wednesday', 'label.thursday',
                'label.friday', 'label.saturday',
        );
    }

    public function buildDays() {
        return array_combine(range(1, 31), range(1, 31));
    }
}
