<?php

namespace Langgas\SisdikBundle\Form;

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
class VendorSekolahType extends AbstractType
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

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $this->getSekolah();

        $builder
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('jenis', 'choice', [
                'choices' => [
                    'standar' => 'label.vendor.sms.standar.sisdik',
                    'khusus' => 'label.vendor.sms.khusus.pilihan',
                ],
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'disabled' => 'disabled',
                    'class' => 'jenis-vendor',
                ],
                'label_attr' => [
                    'class' => 'jenis-vendor',
                ],
            ])
            ->add('urlPengirimPesan', 'text', [
                'label' => 'label.url.pengirim.pesan',
                'required' => false,
                'attr' => [
                    'class' => 'url-vendor-sms',
                ],
                'help_block' => 'help.url.pengirim.pesan.sms',
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\VendorSekolah',
            ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sisdik_vendorsekolah';
    }
}
