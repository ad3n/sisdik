<?php

namespace Fast\SisdikBundle\Form;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiswaGenerateUsernameType extends AbstractType
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
            $querybuilder1 = $em->createQueryBuilder()->select('t')
                    ->from('FastSisdikBundle:TahunMasuk', 't')->where('t.sekolah = :sekolah')
                    ->orderBy('t.tahun', 'DESC')->setParameter('sekolah', $sekolah);
            $builder
                    ->add('tahunmasuk', 'entity',
                            array(
                                    'class' => 'FastSisdikBundle:Tahunmasuk',
                                    'label' => 'label.yearentry.entry', 'multiple' => false,
                                    'expanded' => false, 'property' => 'tahun', 'required' => true,
                                    'query_builder' => $querybuilder1,
                                    'attr' => array(
                                        'class' => 'small selectyear'
                                    ),
                            ));

            $builder
                    ->add('filter', 'text',
                            array(
                                    'label' => 'label.filter.student', 'required' => false,
                                    'attr' => array(
                                        'class' => 'medium'
                                    ),
                            ))
                    ->add('output', 'choice',
                            array(
                                    'choices' => array(
                                            'ods' => 'Open Document Spreadsheet',
                                            'xls' => 'Microsoft Excel 97/2000/XP'
                                    ), 'label' => 'label.output', 'multiple' => false,
                                    'expanded' => true, 'required' => true,
                            ));

            $builder
                    ->add('regenerate', 'checkbox',
                            array(
                                    'label' => 'label.regenerate', 'required' => false,
                                    'help_block' => 'Membuat ulang username akan menimpa username dan password sebelumnya',
                            ))
                    ->add('captcha', 'captcha',
                            array(
                                    'attr' => array(
                                        'class' => 'medium'
                                    ), 'as_url' => true, 'reload' => true,
                            ));
        }
    }

    public function getName() {
        return 'fast_sisdikbundle_siswagenerateusernametype';
    }
}

