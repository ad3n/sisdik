<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Siswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SiswaTahkikType extends AbstractType
{
    private $container;
    private $idsiswa = '';

    public function __construct(ContainerInterface $container, $idsiswa = '') {
        $this->container = $container;
        $this->idsiswa = $idsiswa;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository('FastSisdikBundle:Siswa')
                ->findBy(
                        array(
                            'id' => preg_split('/:/', $this->idsiswa)
                        ));

        foreach ($entities as $entity) {
            if (is_object($entity) && $entity instanceof Siswa) {
                $builder
                        ->add('siswa_' . $entity->getId(), 'checkbox',
                                array(
                                        'required' => false, 'label_render' => false,
                                        'attr' => array(
                                            'class' => 'calon-siswa-check siswa-' . $entity->getId()
                                        )
                                ));
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        // $resolver->setDefaults(array(
        //             'data_class' => 'Fast\SisdikBundle\Entity\Siswa'
        //         ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswatahkiktype';
    }
}
