<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\BiayaSekali;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CalonPembayaranSekaliType extends AbstractType
{
    private $container;
    private $calonSiswaId;

    public function __construct(ContainerInterface $container, $calonSiswaId) {
        $this->container = $container;
        $this->calonSiswaId = $calonSiswaId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $calonSiswa = $em->getRepository('FastSisdikBundle:CalonSiswa')->find($this->calonSiswaId);

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:BiayaSekali', 't')
                ->where('t.tahunmasuk = :tahunmasuk')->andWhere('t.gelombang = :gelombang')
                ->orderBy('t.urutan', 'ASC')
                ->setParameter('tahunmasuk', $calonSiswa->getTahunmasuk()->getId())
                ->setParameter('gelombang', $calonSiswa->getGelombang()->getId());

        $results = $querybuilder->getQuery()->getResult();
        $availableFees = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof BiayaSekali) {
                $availableFees[$entity->getId()] = (strlen($entity->getJenisBiaya()->getNama()) > 25 ? substr(
                                $entity->getJenisbiaya()->getNama(), 0, 22) . '...'
                        : $entity->getJenisbiaya()->getNama()) . ', '
                        . number_format($entity->getNominal(), 0, ',', '.');
            }
        }

        $builder
                ->add('daftarBiayaSekali', 'choice',
                        array(
                                'choices' => $availableFees, 'expanded' => false, 'multiple' => true,
                                'attr' => array(
                                    'class' => 'multiselect',
                                ), 'label_render' => false,
                        ))
                ->add('calonTransaksiPembayaranSekali', 'collection',
                        array(
                                'type' => new CalonTransaksiPembayaranSekaliType(), 'by_reference' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label' => 'label.applicant.oncefee.transaction',
                                'options' => array(
                                    'widget_control_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'allow_add' => true, 'allow_delete' => true,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\CalonPembayaranSekali'
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_calonpembayaransekalitype';
    }
}
