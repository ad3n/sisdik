<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Kelas;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaInisiasiType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Kelas
     */
    private $kelas;

    /**
     * @var string
     */
    private $tanggal;

    /**
     * @param ContainerInterface $container
     * @param Kelas              $kelas
     * @param string             $tanggal
     */
    public function __construct(ContainerInterface $container, Kelas $kelas, $tanggal)
    {
        $this->container = $container;
        $this->kelas = $kelas;
        $this->tanggal = $tanggal;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('statusKehadiran', 'choice', [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('kelas', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'FastSisdikBundle:Kelas',
                'data' => $this->kelas->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'data' => $this->tanggal,
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_kehadiransiswainisiasitype';
    }
}
