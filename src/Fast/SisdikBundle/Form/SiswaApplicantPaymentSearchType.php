<?php
namespace Fast\SisdikBundle\Form;

use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SiswaApplicantPaymentSearchType extends AbstractType
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('t')
                ->from('FastSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')
                ->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah->getId())
            ;
            $builder
                ->add('tahun', 'entity', [
                    'class' => 'FastSisdikBundle:Tahun',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'tahun',
                    'empty_value' => 'label.selectyear',
                    'required' => false,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'small',
                    ],
                    'label_render' => false,
                    'horizontal' => false,
                ])
            ;
        }

        $builder
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
                'attr' => [],
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
                'attr' => [],
                'label_render' => true,
                'label' => 'label.search.paymentnotcomplete',
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
        return 'searchform';
    }
}
