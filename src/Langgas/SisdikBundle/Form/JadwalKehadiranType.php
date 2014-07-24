<?php

namespace Langgas\SisdikBundle\Form;

use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class JadwalKehadiranType extends AbstractType
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
        $user = $this->container
            ->get('security.context')
            ->getToken()
            ->getUser()
        ;
        $sekolah = $user->getSekolah();

        $em = $this->container->get('doctrine')->getManager();

        $builder
            ->add('sekolah', new EntityHiddenType($em), [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Sekolah',
                'data' => $sekolah->getId(),
            ])
        ;

        $querybuilder1 = $em->createQueryBuilder()
            ->select('tahunAkademik')
            ->from('LanggasSisdikBundle:TahunAkademik', 'tahunAkademik')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('tahunAkademik', 'entity', [
                'class' => 'LanggasSisdikBundle:TahunAkademik',
                'label' => 'label.year.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder1,
                'attr' => [
                    'class' => 'medium selectyear',
                ],
            ])
        ;

        $querybuilder2 = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('kelas', 'entity', [
                'class' => 'LanggasSisdikBundle:Kelas',
                'label' => 'label.class.entry',
                'multiple' => false,
                'expanded' => false,
                'property' => 'nama',
                'required' => true,
                'query_builder' => $querybuilder2,
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
        ;

        $querybuilder4 = $em->createQueryBuilder()
            ->select('template')
            ->from('LanggasSisdikBundle:Templatesms', 'template')
            ->where('template.sekolah = :sekolah')
            ->orderBy('template.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;
        $builder
            ->add('templatesms', 'entity', [
                'class' => 'LanggasSisdikBundle:Templatesms',
                'label' => 'label.sms.template.entry',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'property' => 'optionLabel',
                'query_builder' => $querybuilder4,
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
        $resolver->setDefaults([
            'data_class' => 'Langgas\SisdikBundle\Entity\JadwalKehadiran',
        ]);
    }

    public function getName()
    {
        return 'langgas_sisdikbundle_jadwalkehadirantype';
    }
}
