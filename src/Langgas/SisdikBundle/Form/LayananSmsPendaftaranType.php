<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class LayananSmsPendaftaranType extends AbstractType
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

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('jenisLayanan', 'choice', [
                'choices' => PilihanLayananSms::getDaftarLayananPendaftaran(),
                'required' => true,
                'label' => 'label.layanansms.jenis',
            ])
        ;

        $querybuilder1 = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:Templatesms', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.nama', 'ASC')
            ->setParameter('sekolah', $sekolah->getId())
        ;
        $builder
            ->add('templatesms', 'entity', [
                'class' => 'LanggasSisdikBundle:Templatesms',
                'label' => 'label.sms.template.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\LayananSmsPendaftaran',
            ])
        ;
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_layanansmspendaftarantype';
    }
}
