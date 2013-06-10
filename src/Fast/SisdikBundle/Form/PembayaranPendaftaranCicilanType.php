<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;

class PembayaranPendaftaranCicilanType extends AbstractType
{
    private $container;
    private $siswaId;
    private $biaya;

    public function __construct(ContainerInterface $container, $siswaId, $biaya) {
        $this->container = $container;
        $this->siswaId = $siswaId;
        $this->biaya = $biaya;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($this->siswaId);

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't')->where('t.tahun = :tahun')
                ->setParameter('tahun', $siswa->getTahun()->getId())->andWhere('t.gelombang = :gelombang')
                ->setParameter('gelombang', $siswa->getGelombang()->getId())->orderBy('t.urutan', 'ASC')
                ->andWhere('t.id = ?1')->setParameter(1, $this->biaya);

        $results = $querybuilder->getQuery()->getResult();
        $availableFees = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof BiayaPendaftaran) {
                $availableFees[$entity->getId()] = (strlen($entity->getJenisBiaya()->getNama()) > 25 ? substr(
                                $entity->getJenisbiaya()->getNama(), 0, 22) . '...'
                        : $entity->getJenisbiaya()->getNama()) . ', '
                        . number_format($entity->getNominal(), 0, ',', '.');
            }
        }

        $builder
                ->add('daftarBiayaPendaftaran', 'choice',
                        array(
                                'choices' => $availableFees, 'expanded' => false, 'multiple' => false,
                                'attr' => array(
                                    'class' => 'xlarge'
                                ), 'label_render' => true, 'label' => 'label.fee.registration.entry'
                        ))
                ->add('transaksiPembayaranPendaftaran', 'collection',
                        array(
                                'type' => new TransaksiPembayaranPendaftaranType($this->container),
                                'by_reference' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.fee.registration.transaction',
                                'options' => array(
                                    'widget_control_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'allow_add' => true, 'allow_delete' => true,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\PembayaranPendaftaran'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_pembayaranpendaftarantype';
    }
}
