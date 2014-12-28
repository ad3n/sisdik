<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Siswa;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SiswaTahkikType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @InjectParams({
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entities = $this->entityManager
            ->getRepository('LanggasSisdikBundle:Siswa')
            ->findBy([
                'id' => preg_split('/:/', $options['idsiswa']),
            ])
        ;

        foreach ($entities as $entity) {
            if (is_object($entity) && $entity instanceof Siswa) {
                $builder
                    ->add('siswa_'.$entity->getId(), 'checkbox', [
                        'required' => false,
                        'label_render' => false,
                        'attr' => [
                            'class' => 'calon-siswa-check siswa-'.$entity->getId(),
                        ],
                    ])
                ;
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'idsiswa' => [],
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_tahkik';
    }
}
