<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
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
class SekolahAsalType extends AbstractType
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
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('nama', 'text', [
                'required' => true,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.nama.sekolah',
            ])
            ->add('kode', 'text', [
                'required' => true,
                'attr' => [
                    'class' => 'mini',
                ],
                'label' => 'label.kode.sekolah',
            ])
            ->add('alamat', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'xlarge',
                ],
                'label' => 'label.alamat',
            ])
            ->add('penghubung', 'text', [
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label' => 'label.nama.penghubung',
            ])
            ->add('ponselPenghubung', null, [
                'label' => 'label.nomor.ponsel.penghubung',
                'attr' => array(
                    'class' => 'medium',
                ),
                'required' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\SekolahAsal',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_sekolahasal';
    }
}
