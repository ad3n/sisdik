<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaApplicantSearchType extends AbstractType
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
            $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Gelombang', 't')
                    ->where('t.sekolah = :sekolah')->orderBy('t.urutan', 'ASC')
                    ->setParameter('sekolah', $sekolah->getId());
            $builder
                    ->add('gelombang', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Gelombang', 'multiple' => false,
                                    'expanded' => false, 'property' => 'nama',
                                    'empty_value' => 'label.selectadmissiongroup', 'required' => false,
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
