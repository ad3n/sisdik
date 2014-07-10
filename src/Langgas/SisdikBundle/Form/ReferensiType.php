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
class ReferensiType extends AbstractType
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
            ->add('nama', null, [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.name.full'
            ])
            ->add('ponsel', null, [
                'required' => false,
                'attr' => [
                    'class' => 'medium',
                ],
                'label' => 'label.ponsel',
            ])
            ->add('alamat', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.alamat',
            ])
            ->add('nomorIdentitas', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.nomor.identitas',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\Referensi'
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_referensitype';
    }
}
