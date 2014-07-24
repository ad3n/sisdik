<?php

namespace Langgas\SisdikBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SekolahAsalType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('nama', 'text', [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.nama.sekolah',
            ])
            ->add('kode', 'text', [
                'required' => true,
                'attr' => [
                    'class' => 'mini',
                ],
                'label' => 'label.kode.sekolah',
            ])
            ->add('alamat', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'label' => 'label.alamat',
            ])
            ->add('penghubung', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.nama.penghubung',
            ])
            ->add('ponselPenghubung', null, [
                'label' => 'label.nomor.ponsel.penghubung',
                'attr' => array(
                    'class' => 'medium',
                ),
                'required' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\SekolahAsal',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_sekolahasaltype';
    }
}
