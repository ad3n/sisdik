<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;

class PembayaranPendaftaranType extends AbstractType
{
    private $container;
    private $siswaId;
    private $biayaTerbayar;

    public function __construct(ContainerInterface $container, $siswaId, $biayaTerbayar) {
        $this->container = $container;
        $this->siswaId = $siswaId;
        $this->biayaTerbayar = $biayaTerbayar;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($this->siswaId);

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't')->leftJoin('t.jenisbiaya', 't2')
                ->where('t.tahun = :tahun')->setParameter('tahun', $siswa->getTahun()->getId())
                ->andWhere('t.gelombang = :gelombang')
                ->setParameter('gelombang', $siswa->getGelombang()->getId())->orderBy('t.urutan', 'ASC')
                ->addOrderBy('t2.nama', 'ASC');

        if (count($this->biayaTerbayar) > 0) {
            $querybuilder->andWhere('t.id NOT IN (?1)')->setParameter(1, $this->biayaTerbayar);
        }

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
                                'choices' => $availableFees, 'expanded' => true, 'multiple' => true,
                                'attr' => array(
                                    'class' => 'fee-item'
                                ), 'label_render' => true, 'label' => 'label.fee.registration.entry',
                                'required' => true,
                        ))
                ->add('adaPotongan', 'checkbox',
                        array(
                                'label' => 'label.discount', 'required' => false,
                                'attr' => array(
                                    'class' => 'discount-check'
                                ), 'widget_checkbox_label' => 'widget',
                        ))
                ->add('jenisPotongan', 'choice',
                        array(
                                'label' => 'label.discount', 'required' => false,
                                'choices' => $this->buildJenisPotongan(), 'expanded' => true,
                                'multiple' => false, 'label_render' => false,
                                'attr' => array(
                                    'class' => 'discount-type'
                                )
                        ))
                ->add('persenPotongan', 'percent',
                        array(
                                'type' => 'integer', 'required' => false, 'precision' => 0,
                                'attr' => array(
                                    'class' => 'small percentage-discount', 'autocomplete' => 'off'
                                ), 'label' => 'label.discount.percentage',
                        ))
                ->add('nominalPotongan', 'money',
                        array(
                                'currency' => 'IDR', 'required' => false, 'precision' => 0, 'grouping' => 3,
                                'attr' => array(
                                    'class' => 'medium nominal-discount', 'autocomplete' => 'off'
                                ), 'label' => 'label.discount.amount',
                        ))
                ->add('transaksiPembayaranPendaftaran', 'collection',
                        array(
                                'type' => new TransaksiPembayaranPendaftaranType(), 'by_reference' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.fee.registration.transaction',
                                'options' => array(
                                    'widget_control_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'allow_add' => true, 'allow_delete' => true,
                        ));
    }

    public function buildJenisPotongan() {
        return array(
            'nominal' => 'nominal', 'persentase' => 'persentase'
        );
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
