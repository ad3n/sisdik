<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class CariUserType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

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
     *     "securityContext" = @Inject("security.context"),
     *     "entityManager" = @Inject("doctrine.orm.entity_manager"),
     *     "translator" = @Inject("translator")
     * })
     *
     * @param SecurityContext $securityContext
     * @param EntityManager   $entityManager
     * @param Translator      $translator
     */
    public function __construct(SecurityContext $securityContext, EntityManager $entityManager, Translator $translator)
    {
        $this->securityContext = $securityContext;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['mode_superadmin'] === true) {
            $builder
                ->add('searchoption', 'choice', [
                    'choices' => $this->buildChoices(),
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'large',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        }
        $builder
            ->add('searchkey', null, [
                'label' => 'label.searchkey',
                'required' => false,
                'attr' => [
                    'class' => 'medium search-query',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('nonSiswa', 'checkbox', [
                'required' => false,
                'attr' => [],
                'label_render' => true,
                'label' => 'label.bukan.siswa',
                'widget_checkbox_label' => 'widget',
                'horizontal' => false,
            ])
        ;
    }

    private function buildChoices()
    {
        $entities = $this->entityManager->getRepository('LanggasSisdikBundle:Sekolah')->findBy([], ['nama' => 'ASC']);

        $choices = [
            '' => 'label.all',
            'unset' => $this->translator->trans('label.tanpa.sekolah'),
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
                'mode_superadmin' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_cariuser';
    }
}
