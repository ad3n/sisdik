<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaApplicantPaymentSearchType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahunmasuk', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.tahun', 'DESC')
                    ->setParameter('sekolah', $sekolah->getId());

            $builder
                    ->add('tahunmasuk', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahunmasuk',
                                    'label' => 'label.yearentry.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'tahun',
                                    'empty_value' => 'label.selectyear', 'required' => false,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'label_render' => false,
                            ));
        }
        $builder
                ->add('searchkey', null,
                        array(
                                'required' => false,
                                'attr' => array(
                                    'class' => 'medium search-query', 'placeholder' => 'label.searchkey'
                                ), 'label_render' => false,
                        ))
                ->add('nopayment', 'checkbox',
                        array(
                                'required' => false, 'attr' => array(), 'label_render' => true,
                                'label' => 'label.search.notpaid', 'widget_checkbox_label' => 'widget',
                        ))
                ->add('todayinput', 'checkbox',
                        array(
                                'required' => false, 'attr' => array(), 'label_render' => true,
                                'label' => 'label.search.today.applicant',
                                'widget_checkbox_label' => 'widget',
                        ))
                ->add('notsettled', 'checkbox',
                        array(
                                'required' => false, 'attr' => array(), 'label_render' => true,
                                'label' => 'label.search.paymentnotcomplete',
                                'widget_checkbox_label' => 'widget',
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'csrf_protection' => false,
                        ));
    }

    public function getName() {
        return 'searchform';
    }
}

