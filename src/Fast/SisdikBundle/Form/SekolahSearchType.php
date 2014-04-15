<?php
namespace Fast\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * @FormType
 */
class SekolahSearchType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @InjectParams({
     *     "entityManager" = @Inject("doctrine.orm.entity_manager"),
     *     "translator" = @Inject("translator")
     * })
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('sekolah', 'choice', [
            'choices' => $this->buildSchoolChoices(),
            'multiple' => false,
            'expanded' => false,
            'required' => false,
            'attr' => [
                'class' => 'large'
            ],
            'label_render' => false,
            'horizontal' => false,
        ]);
    }

    /**
     * @return array
     */
    private function buildSchoolChoices()
    {
        $em = $this->entityManager;
        $entities = $em->getRepository('FastSisdikBundle:Sekolah')
            ->findBy([], [
                'nama' => 'ASC'
            ])
        ;

        $choices = [
            '' => $this->translator->trans('label.semua.sekolah'),
        ];

        foreach ($entities as $entity) {
            if ($entity instanceof Sekolah) {
                $choices[$entity->getId()] = $entity->getNama();
            }
        }

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
        return 'sisdik_cari_sekolah';
    }
}
