<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\SekolahSubscriber;
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
class MesinKehadiranType extends AbstractType
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

        $builder->addEventSubscriber(new SekolahSubscriber($sekolah));

        $builder
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('alamatIp', null, [
                'label' => 'label.ipaddress',
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('commkey', null, [
                'label' => 'label.commkey',
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('webUsername', null, [
                'label' => 'label.web.username',
                'required' => false,
            ])
            ->add('webPassword', null, [
                'label' => 'label.web.password',
                'required' => false,
            ])
            ->add('waktuTertibHarian', 'time', [
                'label' => 'label.waktu.tertib.harian',
                'required' => false,
                'input' => 'string',
                'widget' => 'single_text',
                'with_seconds' => false,
                'help_block' => 'help.waktu.tertib.harian',
            ])
            ->add('aktif', null, [
                'label' => 'label.active',
                'required' => false,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\MesinKehadiran',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_mesinkehadiran';
    }
}
