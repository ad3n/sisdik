<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\EventListener\SekolahSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class SekolahAsalType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @InjectParams({
     *     "tokenStorage" = @Inject("security.token_storage")
     * })
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->tokenStorage->getToken()->getUser()->getSekolah();
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
