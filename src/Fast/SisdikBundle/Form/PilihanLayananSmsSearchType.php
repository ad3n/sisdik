<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class PilihanLayananSmsSearchType extends AbstractType
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
        $builder
            ->add('sekolah', 'choice', [
                'choices' => $this->buildSchoolChoices(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'large'
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('jenisLayanan', 'choice', [
                'choices' => $this->buildServiceChoices(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'large'
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    private function buildSchoolChoices()
    {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository('FastSisdikBundle:Sekolah')->findBy([], ['nama' => 'ASC']);

        $choices = [
            '' => 'label.allschool',
        ];

        foreach ($entities as $entity) {
            if ($entity instanceof Sekolah) {
                $choices[$entity->getId()] = $entity->getNama();
            }
        }

        return $choices;
    }

    private function buildServiceChoices()
    {
        $choices = array_merge(
            ['' => 'label.semua.layanan'],
            PilihanLayananSms::getDaftarLayananPendaftaran(),
            PilihanLayananSms::getDaftarLayananLaporan(),
            PilihanLayananSms::getDaftarLayananKehadiran()
        );

        return $choices;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'searchform';
    }
}
