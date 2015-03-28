<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class JadwalDuplicateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sekolah = $options['sekolahSrc'];

        $builder
            ->add('sekolahSrc', 'hidden', [
                'data' => $options['sekolahSrc'],
            ])
            ->add('tahunAkademikSrc', 'hidden', [
                'data' => $options['tahunAkademikSrc'],
            ])
            ->add('kelasSrc', 'hidden', [
                'data' => $options['kelasSrc'],
            ])
            ->add('perulanganSrc', 'hidden', [
                'data' => $options['perulanganSrc'],
            ])
            ->add('requestUri', 'hidden', [
                'data' => $options['requestUri'],
            ])
            ->add('mingguanHariKeSrc', 'hidden', [
                'data' => $options['mingguanHariKeSrc'],
            ])
            ->add('bulananHariKeSrc', 'hidden', [
                'data' => $options['bulananHariKeSrc'],
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
                    'class' => 'medium selectyearduplicate',
                ],
                'label_render' => false,
                'horizontal' => false,
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
                    'class' => 'medium selectclassduplicate',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('perulangan', 'choice', [
                'choices' => JadwalKehadiran::getDaftarPerulangan(),
                'label' => 'label.selectrepetition',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => array(
                    'class' => 'small',
                ),
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('mingguanHariKe', 'choice', [
                'choices' => JadwalKehadiran::getNamaHari(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'label.selectweekday',
                'attr' => array(
                    'class' => 'medium',
                ),
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('bulananHariKe', 'choice', [
                'choices' => JadwalKehadiran::getAngkaHariSebulan(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'label.selectmonthday',
                'attr' => [
                    'class' => 'medium',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
                'sekolahSrc' => null,
                'tahunAkademikSrc' => null,
                'kelasSrc' => null,
                'perulanganSrc' => null,
                'requestUri' => null,
                'mingguanHariKeSrc' => null,
                'bulananHariKeSrc' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_salinjadwal';
    }
}
