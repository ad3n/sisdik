<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class CariPembayarBiayaPendaftaranType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @InjectParams({
     *     "tokenStorage" = @Inject("security.token_storage")
     * })
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'placeholder' => 'label.selectyear',
                'required' => false,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahun')
                        ->where('tahun.sekolah = :sekolah')
                        ->orderBy('tahun.tahun', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'small',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('searchkey', null, [
                'required' => false,
                'attr' => [
                    'class' => 'medium search-query',
                    'placeholder' => 'label.searchkey',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('nopayment', 'checkbox', [
                'required' => false,
                'attr' => [
                    'class' => 'belum-bayar',
                ],
                'label_render' => true,
                'label' => 'label.search.notpaid',
                'widget_checkbox_label' => 'widget',
                'horizontal' => false,
            ])
            ->add('todayinput', 'checkbox', [
                'required' => false,
                'attr' => [],
                'label_render' => true,
                'label' => 'label.search.today.applicant',
                'widget_checkbox_label' => 'widget',
                'horizontal' => false,
            ])
            ->add('notsettled', 'checkbox', [
                'required' => false,
                'attr' => [
                    'class' => 'belum-lunas',
                ],
                'label_render' => true,
                'label' => 'label.search.paymentnotcomplete',
                'widget_checkbox_label' => 'widget',
                'horizontal' => false,
            ])
            ->add('adaRestitusi', 'checkbox', [
                'required' => false,
                'attr' => [
                    'class' => 'ada-restitusi',
                ],
                'label_render' => true,
                'label' => 'label.direstitusi',
                'widget_checkbox_label' => 'widget',
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
        return 'sisdik_caripembayarbiayapendaftaran';
    }
}
