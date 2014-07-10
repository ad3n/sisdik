<?php
namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Siswa;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaTahkikType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $idsiswa = '';

    /**
     * @param ContainerInterface $container
     * @param string             $idsiswa
     */
    public function __construct(ContainerInterface $container, $idsiswa = '')
    {
        $this->container = $container;
        $this->idsiswa = $idsiswa;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->container->get('doctrine')->getManager();
        $entities = $em->getRepository('LanggasSisdikBundle:Siswa')
            ->findBy([
                'id' => preg_split('/:/', $this->idsiswa),
            ])
        ;

        foreach ($entities as $entity) {
            if (is_object($entity) && $entity instanceof Siswa) {
                $builder
                    ->add('siswa_' . $entity->getId(), 'checkbox', [
                        'required' => false,
                        'label_render' => false,
                        'attr' => [
                            'class' => 'calon-siswa-check siswa-' . $entity->getId(),
                        ],
                    ])
                ;
            }
        }
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_siswatahkiktype';
    }
}
