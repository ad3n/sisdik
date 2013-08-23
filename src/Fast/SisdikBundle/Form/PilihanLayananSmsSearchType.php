<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PilihanLayananSmsSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('sekolah', 'choice',
                        array(
                                'choices' => $this->buildSchoolChoices(), 'multiple' => false,
                                'expanded' => false, 'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label_render' => false,
                        ));
        $builder
                ->add('jenisLayanan', 'choice',
                        array(
                                'choices' => $this->buildServiceChoices(), 'multiple' => false,
                                'expanded' => false, 'required' => false,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'label_render' => false,
                        ));
    }

    private function buildSchoolChoices() {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository('FastSisdikBundle:Sekolah')
                ->findBy(array(), array(
                    'nama' => 'ASC'
                ));
        $choices = array(
            '' => 'label.allschool',
        );
        foreach ($entities as $entity) {
            if ($entity instanceof Sekolah) {
                $choices[$entity->getId()] = $entity->getNama();
            }
        }

        return $choices;
    }

    private function buildServiceChoices() {
        $choices = array_merge(array(
                    '' => 'label.semua.layanan'
                ), PilihanLayananSms::getDaftarLayananPendaftaran(),
                PilihanLayananSms::getDaftarLayananLaporan(), PilihanLayananSms::getDaftarLayananKehadiran());

        return $choices;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'searchform';
    }
}

