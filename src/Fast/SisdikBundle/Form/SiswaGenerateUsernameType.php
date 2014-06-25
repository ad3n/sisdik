<?php
namespace Fast\SisdikBundle\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class SiswaGenerateUsernameType extends AbstractType
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
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder1 = $em->createQueryBuilder()
                ->select('tahun')
                ->from('FastSisdikBundle:Tahun', 'tahun')
                ->where('tahun.sekolah = :sekolah')
                ->orderBy('tahun.tahun', 'DESC')
                ->setParameter('sekolah', $sekolah)
            ;
            $builder
                ->add('tahun', 'entity', [
                    'class' => 'FastSisdikBundle:Tahun',
                    'label' => 'label.year.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'property' => 'tahun',
                    'required' => true,
                    'query_builder' => $querybuilder1,
                    'attr' => [
                        'class' => 'medium selectyear',
                    ],
                    'empty_value' => 'label.selectyear',
                ])
                ->add('filter', 'text', [
                    'label' => 'label.filter.student',
                    'required' => false,
                    'attr' => [
                        'class' => 'large studentfilter ketik-pilih-tambah',
                        'placeholder' => 'help.filterby.name.systemid',
                    ],
                ])
                ->add('output', 'choice', [
                    'choices' => [
                        'ods' => 'Open Document Spreadsheet',
                        'xls' => 'Microsoft Excel 97/2000/XP',
                    ],
                    'label' => 'label.output',
                    'multiple' => false,
                    'expanded' => true,
                    'required' => true,
                    'data' => 'ods',
                ])
                ->add('regenerate', 'checkbox', [
                    'label' => 'label.regenerate',
                    'required' => false,
                    'help_block' => 'help.regenerate.username',
                    'attr' => [
                        'class' => 'regenerate-username',
                    ],
                    'widget_checkbox_label' => 'widget',
                    'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
                ])
                ->add('captcha', 'captcha', [
                    'attr' => [
                        'class' => 'medium',
                        'placeholder' => 'help.type.captcha',
                        'autocomplete' => 'off',
                    ],
                    'as_url' => true,
                    'reload' => true,
                    'help_block' => 'help.captcha.username.explain',
                ])
            ;
        }
    }

    public function getName()
    {
        return 'fast_sisdikbundle_siswagenerateusernametype';
    }
}
