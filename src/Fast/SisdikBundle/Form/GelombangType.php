<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class GelombangType extends AbstractType
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
                'class' => 'FastSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('nama', null, [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
            ])
            ->add('kode', null, [
                'required' => true,
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('keterangan', null, [
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('urutan', 'choice', [
                'choices' => $this->buildOrderChoices(),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'attr' => [
                    'class' => 'small',
                ],
            ])
        ;
    }

    /**
     * @return array
     */
    public function buildOrderChoices()
    {
        return array_combine(range(1, 100), range(1, 100));
    }

    public function getName()
    {
        return 'fast_sisdikbundle_gelombangtype';
    }
}
