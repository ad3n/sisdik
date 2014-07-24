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
     * @var Sekolah
     */
    private $sekolah;

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

        $this->sekolah = $this->securityContext->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $querybuilder1 = $this->entityManager
            ->createQueryBuilder()
            ->select('tahun')
            ->from('LanggasSisdikBundle:Tahun', 'gelombang')
            ->where('gelombang.sekolah = :sekolah')
            ->orderBy('gelombang.tahun', 'DESC')
            ->setParameter('sekolah', $this->sekolah)
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

        $querybuilder2 = $this->entityManager
            ->createQueryBuilder()
            ->select('gelombang')
            ->from('LanggasSisdikBundle:Gelombang', 'gelombang')
            ->where('gelombang.sekolah = :sekolah')
            ->orderBy('gelombang.urutan', 'ASC')
            ->setParameter('sekolah', $this->sekolah)
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
