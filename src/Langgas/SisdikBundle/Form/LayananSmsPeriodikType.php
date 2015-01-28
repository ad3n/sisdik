<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\LayananSmsPeriodik;
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
class LayananSmsPeriodikType extends AbstractType
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
            ->add('jenisLayanan', 'choice', [
                'choices' => array_merge(
                    PilihanLayananSms::getDaftarLayananPeriodik()
                ),
                'required' => true,
                'label' => 'label.layanansms.jenis',
            ])
            ->add('perulangan', 'choice', [
                'choices' => LayananSmsPeriodik::getDaftarPerulangan(),
                'label' => 'label.perulangan',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'small',
                ],
            ])
            ->add('mingguanHariKe', 'choice', [
                'label' => 'label.day',
                'choices' => JadwalKehadiran::getNamaHari(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'label.selectweekday',
                'attr' => [
                    'class' => 'medium',
                ],
                'help_block' => 'help.untuk.perulangan.mingguan',
            ])
            ->add('bulananHariKe', 'choice', [
                'label' => 'label.monthday',
                'choices' => JadwalKehadiran::getAngkaHariSebulan(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'label.selectmonthday',
                'attr' => [
                    'class' => 'medium',
                ],
                'help_block' => 'help.untuk.perulangan.bulanan.atau.lebih.lama',
            ])
            ->add('bulanAwal', 'choice', [
                'label' => 'label.bulan.awal',
                'choices' => LayananSmsPeriodik::getDaftarNamaBulan(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'label.pilih.bulan',
                'attr' => [
                    'class' => 'medium',
                ],
                'help_block' => 'help.untuk.perulangan.triwulan.atau.lebih.lama',
            ])
            ->add('smsJam', 'time', [
                'label' => 'label.kirim.sms.jam',
                'required' => false,
                'input' => 'string',
                'widget' => 'single_text',
                'with_seconds' => false,
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('templatesms', 'entity', [
                'class' => 'LanggasSisdikBundle:Templatesms',
                'label' => 'label.sms.template.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'optionLabel',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('templateSms')
                        ->where('templateSms.sekolah = :sekolah')
                        ->orderBy('templateSms.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
            ])
            ->add('tingkat', 'entity', [
                'class' => 'LanggasSisdikBundle:Tingkat',
                'label' => 'label.tingkat',
                'required' => false,
                'property' => 'optionLabel',
                'empty_value' => 'label.pilih.tingkat',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tingkat')
                        ->where('tingkat.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
            ])
            ->add('aktif', 'checkbox', [
                'label' => 'label.active',
                'required' => false,
                'label_render' => true,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => 'Langgas\SisdikBundle\Entity\LayananSmsPeriodik',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_layanansmsperiodik';
    }
}
