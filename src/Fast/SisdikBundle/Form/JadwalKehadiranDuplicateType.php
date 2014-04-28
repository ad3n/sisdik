<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JadwalKehadiranDuplicateType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var integer
     */
    private $sekolahSrc;

    /**
     * @var integer|NULL
     */
    private $tahunAkademikSrc = NULL;

    /**
     * @var integer|NULL
     */
    private $kelasSrc = NULL;

    /**
     * @var string|NULL
     */
    private $perulanganSrc = NULL;

    /**
     * @var string|NULL
     */
    private $requestUri = NULL;

    /**
     * @var integer|NULL
     */
    private $mingguanHariKeSrc = NULL;

    /**
     * @var integer|NULL
     */
    private $bulananHariKeSrc = NULL;

    /**
     * @param ContainerInterface $container
     * @param integer            $sekolah
     * @param integer            $tahunAkademik
     * @param integer            $kelas
     * @param string             $perulangan
     * @param string             $requestUri
     * @param integer            $mingguanHariKe
     * @param integer            $bulananHariKe
     */
    public function __construct(ContainerInterface $container, $sekolah, $tahunAkademik = NULL, $kelas = NULL, $perulangan = NULL, $requestUri = NULL, $mingguanHariKe = NULL, $bulananHariKe = NULL)
    {
        $this->container = $container;
        $this->sekolahSrc = $sekolah;
        $this->tahunAkademikSrc = $tahunAkademik;
        $this->kelasSrc = $kelas;
        $this->perulanganSrc = $perulangan;
        $this->requestUri = $requestUri;
        $this->mingguanHariKeSrc = $mingguanHariKe;
        $this->bulananHariKeSrc = $bulananHariKe;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sekolahSrc', 'hidden', [
                'data' => $this->sekolahSrc,
            ])
            ->add('tahunAkademikSrc', 'hidden', [
                'data' => $this->tahunAkademikSrc,
            ])
            ->add('kelasSrc', 'hidden', [
                'data' => $this->kelasSrc,
            ])
            ->add('perulanganSrc', 'hidden', [
                'data' => $this->perulanganSrc,
            ])
            ->add('requestUri', 'hidden', [
                'data' => $this->requestUri,
            ])
            ->add('mingguanHariKeSrc', 'hidden', [
                'data' => $this->mingguanHariKeSrc,
            ])
            ->add('bulananHariKeSrc', 'hidden', [
                'data' => $this->bulananHariKeSrc,
            ])
        ;

        $querybuilder1 = $em->createQueryBuilder()
            ->select('t')
            ->from('FastSisdikBundle:TahunAkademik', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.urutan', 'DESC')
            ->setParameter('sekolah', $this->sekolahSrc)
        ;
        $builder
            ->add('tahunAkademik', 'entity', [
                'class' => 'FastSisdikBundle:TahunAkademik',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'medium selectyearduplicate',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('t')
            ->from('FastSisdikBundle:Kelas', 't')
            ->leftJoin('t.tingkat', 't2')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t2.urutan', 'ASC')
            ->addOrderBy('t.urutan')
            ->setParameter('sekolah', $this->sekolahSrc)
        ;
        $builder
            ->add('kelas', 'entity', [
                'class' => 'FastSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'medium selectclassduplicate',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $builder
            ->add('perulangan', 'choice', [
                'choices' => JadwalKehadiran::getDaftarPerulangan(),
                'label' => 'label.selectrepetition',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'small',
                ),
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('mingguanHariKe', 'choice', [
                'choices' => JadwalKehadiran::getNamaHari(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'label.selectweekday',
                'attr' => array(
                    'class' => 'medium',
                ),
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('bulananHariKe', 'choice', [
                'choices' => JadwalKehadiran::getAngkaHariSebulan(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'label.selectmonthday',
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_jadwalkehadiranduplicatetype';
    }
}
