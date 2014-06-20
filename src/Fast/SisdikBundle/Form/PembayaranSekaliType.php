<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\BiayaSekali;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PembayaranSekaliType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var integer
     */
    private $siswaId;

    /**
     * @param ContainerInterface $container
     * @param string             $siswaId
     */
    public function __construct(ContainerInterface $container, $siswaId)
    {
        $this->container = $container;
        $this->siswaId = $siswaId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($this->siswaId);

        $querybuilder = $em->createQueryBuilder()
            ->select('biaya')
            ->from('FastSisdikBundle:BiayaSekali', 'biaya')
            ->where('biaya.tahun = :tahun')
            ->andWhere('biaya.gelombang = :gelombang')
            ->orderBy('biaya.urutan', 'ASC')
            ->setParameter('tahun', $siswa->getTahun())
            ->setParameter('gelombang', $siswa->getGelombang())
        ;
        $results = $querybuilder->getQuery()->getResult();

        $availableFees = [];
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof BiayaSekali) {
                $availableFees[$entity->getId()] =
                    (
                        strlen($entity->getJenisBiaya()->getNama()) > 25 ? substr($entity->getJenisbiaya()->getNama(), 0, 22) . '...' : $entity->getJenisbiaya()->getNama()
                    )
                    . ', '
                    . number_format($entity->getNominal(), 0, ',', '.')
                ;
            }
        }

        $builder
            ->add('daftarBiayaSekali', 'choice', [
                'choices' => $availableFees,
                'expanded' => false,
                'multiple' => true,
                'attr' => array(
                    'class' => 'multiselect',
                ),
                'label_render' => false,
            ])
            ->add('transaksiPembayaranSekali', 'collection', [
                'type' => new TransaksiPembayaranSekaliType(),
                'by_reference' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.applicant.oncefee.transaction',
                'options' => [
                    'label_render' => false,
                ],
                'label_render' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Fast\SisdikBundle\Entity\PembayaranSekali',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_pembayaransekalitype';
    }
}
