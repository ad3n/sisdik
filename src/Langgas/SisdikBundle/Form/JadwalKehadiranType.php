<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
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
class JadwalKehadiranType extends AbstractType
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

        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sekolah', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
            ->add('tahunAkademik', 'entity', [
                'class' => 'LanggasSisdikBundle:TahunAkademik',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('tahunAkademik')
                        ->where('tahunAkademik.sekolah = :sekolah')
                        ->orderBy('tahunAkademik.urutan', 'DESC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'medium selectyear',
                ],
            ])
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('kelas')
                        ->leftJoin('kelas.tingkat', 'tingkat')
                        ->where('kelas.sekolah = :sekolah')
                        ->orderBy('tingkat.urutan', 'ASC')
                        ->addOrderBy('kelas.urutan')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'large selectclass',
                ],
            ])
            ->add('statusKehadiran', 'choice', [
                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                'label' => 'label.status.kehadiran',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'medium',
                ],
            ])
            ->add('perulangan', 'choice', [
                'choices' => JadwalKehadiran::getDaftarPerulangan(),
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
            ])
            ->add('paramstatusDariJam', 'time', [
                'label' => 'label.paramstatusfrom',
                'required' => false,
                'input' => 'string',
                'widget' => 'single_text',
                'with_seconds' => false,
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('paramstatusHinggaJam', 'time', [
                'label' => 'label.paramstatusto',
                'required' => false,
                'input' => 'string',
                'widget' => 'single_text',
                'with_seconds' => false,
                'attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('kirimSms', 'checkbox', [
                'label' => 'label.kirim.sms',
                'required' => false,
                'label_render' => true,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
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
                'required' => false,
                'property' => 'optionLabel',
                'query_builder' => function (EntityRepository $repository) use ($sekolah) {
                    $qb = $repository->createQueryBuilder('template')
                        ->where('template.sekolah = :sekolah')
                        ->orderBy('template.nama', 'ASC')
                        ->setParameter('sekolah', $sekolah)
                    ;

                    return $qb;
                },
                'attr' => [
                    'class' => 'xlarge',
                ],
                'empty_value' => 'label.pilih.template.sms',
            ])
            ->add('otomatisTerhubungMesin', 'checkbox', [
                'label' => 'label.otomatis.terhubung.mesin.kehadiran',
                'required' => false,
                'label_render' => true,
                'widget_checkbox_label' => 'widget',
                'horizontal_input_wrapper_class' => 'col-sm-offset-4 col-sm-8 col-md-offset-4 col-md-7 col-lg-offset-3 col-lg-9',
            ])
            ->add('permulaan', 'checkbox', [
                'label' => 'label.awal.kehadiran',
                'required' => false,
                'help_block' => 'help.awal.kehadiran',
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
                'data_class' => 'Langgas\SisdikBundle\Entity\JadwalKehadiran',
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_jadwalkehadiran';
    }
}
