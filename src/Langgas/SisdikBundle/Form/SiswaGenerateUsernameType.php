<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SiswaGenerateUsernameType extends AbstractType
{
    /**
     * @var SecurityContext
     */
    private $securityContext;

    /**
     * @InjectParams({
     *     "securityContext" = @Inject("security.context")
     * })
     *
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
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
            ->add('tahun', 'entity', [
                'class' => 'LanggasSisdikBundle:Tahun',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'tahun',
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahun')
                        ->where('tahun.sekolah = :sekolah')
                        ->orderBy('tahun.tahun', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
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

    public function getName()
    {
        return 'sisdik_usersiswa';
    }
}
