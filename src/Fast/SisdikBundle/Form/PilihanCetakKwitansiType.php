<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PilihanCetakKwitansiType extends AbstractType
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
            ->add('output', 'choice', [
                'choices' => $this->buildOutputChoices(),
                'required' => true,
                'expanded' => true,
                'multiple' => false,
            ])
        ;
    }

    public function buildOutputChoices()
    {
        return [
            'pdf' => 'label.defaultpdf',
            'esc_p' => 'label.directprint',
        ];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Fast\SisdikBundle\Entity\PilihanCetakKwitansi',
            ])
        ;
    }

    public function getName()
    {
        return 'fast_sisdikbundle_pilihancetakkwitansitype';
    }
}
