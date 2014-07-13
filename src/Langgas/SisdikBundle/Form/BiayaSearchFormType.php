<?php
namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
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
class BiayaSearchFormType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->securityContext->getToken()->getUser()->getSekolah();
        $em = $this->entityManager;

        $querybuilder1 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Tahun', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.tahun', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'empty_value' => 'label.selectyear',
                'required' => false,
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'small'
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Gelombang', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('gelombang', 'entity', [
                'class' => 'LanggasSisdikBundle:Gelombang',
                'label' => 'label.admissiongroup.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'empty_value' => 'label.selectadmissiongroup',
                'required' => false,
                'query_builder' => $querybuilder2,
                'attr' => [
                    'class' => 'medium'
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;

        $builder
            ->add('jenisbiaya', null, [
                'label' => 'label.fee.type.entry',
                'required' => false,
                'attr' => [
                    'class' => 'search-query small',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
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
        return 'sisdik_caribiaya';
    }
}
