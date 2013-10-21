<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\BiayaSekali;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PembayaranSekaliType extends AbstractType
{

    private $container;

    private $siswaId;

    public function __construct(ContainerInterface $container, $siswaId)
    {
        $this->container = $container;
        $this->siswaId = $siswaId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')
            ->getToken()
            ->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($this->siswaId);

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('FastSisdikBundle:BiayaSekali', 't')
            ->where('t.tahun = :tahun')
            ->andWhere('t.gelombang = :gelombang')
            ->orderBy('t.urutan', 'ASC')
            ->setParameter('tahun', $siswa->getTahun()
            ->getId())
            ->setParameter('gelombang', $siswa->getGelombang()
            ->getId());

        $results = $querybuilder->getQuery()->getResult();
        $availableFees = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof BiayaSekali) {
                $availableFees[$entity->getId()] = (strlen($entity->getJenisBiaya()->getNama()) > 25 ? substr($entity->getJenisbiaya()->getNama(), 0, 22) . '...' : $entity->getJenisbiaya()->getNama()) . ', ' . number_format($entity->getNominal(), 0, ',', '.');
            }
        }

        $builder->add('daftarBiayaSekali', 'choice', array(
            'choices' => $availableFees,
            'expanded' => false,
            'multiple' => true,
            'attr' => array(
                'class' => 'multiselect'
            ),
            'label_render' => false
        ))->add('transaksiPembayaranSekali', 'collection', array(
            'type' => new TransaksiPembayaranSekaliType(),
            'by_reference' => false,
            'attr' => array(
                'class' => 'large'
            ),
            'label' => 'label.applicant.oncefee.transaction',
            'options' => array(
                'widget_control_group' => false,
                'label_render' => false
            ),
            'label_render' => false,
            'allow_add' => true,
            'allow_delete' => true
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fast\SisdikBundle\Entity\PembayaranSekali'
        ));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_pembayaransekalitype';
    }
}
