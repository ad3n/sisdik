<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\KelasSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class KehadiranSiswaHapusType extends AbstractType
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
     * @InjectParams({
     *     "securityContext" = @Inject("security.context"),
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param SecurityContext $securityContext
     * @param EntityManager   $entityManager
     */
    public function __construct(SecurityContext $securityContext, EntityManager $entityManager)
    {
        $this->securityContext = $securityContext;
        $this->entityManager = $entityManager;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('tanggal', 'date', [
                'label' => 'label.date',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'date small',
                    'placeholder' => 'label.date',
                    'autocomplete' => 'off',
                ],
                'required' => true,
                'horizontal_input_wrapper_class' => 'col-sm-4 col-md-3 col-lg-2',
            ])
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.tingkat',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'property' => 'optionLabel',
                'empty_value' => 'label.seluruh.tingkat',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tingkat')
                        ->where('tingkat.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan')
                        ->addOrderBy('tingkat.kode')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium hapus-pilih-tingkat',
                ],
                'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
            ])
            ->add('captcha', 'captcha', [
                'attr' => [
                    'class' => 'medium',
                    'placeholder' => 'help.type.captcha',
                    'autocomplete' => 'off',
                ],
                'as_url' => true,
                'reload' => true,
                'help_block' => 'help.captcha.penjelasan.hapus.kehadiran',
                'horizontal_input_wrapper_class' => 'col-sm-6 col-md-5 col-lg-4',
            ])
        ;

        $builder->addEventSubscriber(new KelasSubscriber($this->entityManager, $sekolah, 1));
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswahapus';
    }
}
