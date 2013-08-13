<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaImportType extends AbstractType
{
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();
        $querybuilder1 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Tahun', 't')
                ->where('t.sekolah = :sekolah')->orderBy('t.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah);
        $builder
                ->add('tahun', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Tahun', 'label' => 'label.year.entry',
                                'multiple' => false, 'expanded' => false, 'property' => 'tahun',
                                'required' => true, 'query_builder' => $querybuilder1,
                                'attr' => array(
                                    'class' => 'medium'
                                ),
                        ));

        $builder
                ->add('file', 'file',
                        array(
                            'required' => true,
                        ))
                ->add('captcha', 'captcha',
                        array(
                                'attr' => array(
                                        'class' => 'medium', 'placeholder' => 'help.type.captcha',
                                        'autocomplete' => 'off'
                                ), 'as_url' => true, 'reload' => true,
                                'help_block' => 'help.captcha.penjelasan.unggah.impor.baru',
                        ));
    }

    public function getName() {
        return 'fast_sisdikbundle_siswaimporttype';
    }
}
