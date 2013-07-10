<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Form\EventListener\JadwalKehadiranSearchSubscriber;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class JadwalKehadiranSearchType extends AbstractType
{
    private $container;
    private $repetition = 'harian';

    public function __construct(ContainerInterface $container, $repetition = 'harian') {
        $this->container = $container;
        $this->repetition = $repetition;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $querybuilder1 = $em->createQueryBuilder()->select('tahunAkademik')
                ->from('FastSisdikBundle:TahunAkademik', 'tahunAkademik')
                ->where('tahunAkademik.sekolah = :sekolah')->orderBy('tahunAkademik.urutan', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahunAkademik', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:TahunAkademik', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium selectyear'
                                ), 'label_render' => false,
                        ));

        $querybuilder2 = $em->createQueryBuilder()->select('kelas')->from('FastSisdikBundle:Kelas', 'kelas')
                ->leftJoin('kelas.tingkat', 'tingkat')->where('kelas.sekolah = :sekolah')
                ->orderBy('tingkat.urutan', 'ASC')->addOrderBy('kelas.urutan')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('kelas', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Kelas', 'label' => 'label.class.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true, 'query_builder' => $querybuilder2,
                                'attr' => array(
                                    'class' => 'medium selectclass'
                                ), 'label_render' => false
                        ));

        $builder->addEventSubscriber(new JadwalKehadiranSearchSubscriber($em));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'searchform';
    }
}
