<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\KehadiranSiswa;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KehadiranSiswaSmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statusKehadiran', 'choice', [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => JadwalKehadiran::getDaftarStatusKehadiran(),
                'attr' => [
                    'class' => 'medium',
                ],
                'placeholder' => 'label.pilih.status.kehadiran',
            ])
            ->add('kelas', 'sisdik_entityhidden', [
                'required' => true,
                'class' => 'LanggasSisdikBundle:Kelas',
                'data' => $options['kelas']->getId(),
            ])
            ->add('tanggal', 'hidden', [
                'data' => $options['tanggal'],
            ])
        ;

        $siswa = [];
        foreach ($options['kehadiran'] as $kehadiran) {
            if ($kehadiran instanceof KehadiranSiswa) {
                $siswa[] = $kehadiran->getSiswa()->getId();
            }
        }

        if (count($siswa) > 0) {
            $builder
                ->add('siswa', 'entity', [
                    'class' => 'LanggasSisdikBundle:Siswa',
                    'label' => 'label.student.entry',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false,
                    'property' => 'namaLengkap',
                    'query_builder' => function (EntityRepository $repository) use ($siswa) {
                        $qb = $repository->createQueryBuilder('siswa')
                            ->where('siswa.id IN (:id)')
                            ->orderBy('siswa.namaLengkap', 'ASC')
                            ->setParameter('id', $siswa)
                        ;

                        return $qb;
                    },
                    'attr' => [
                        'class' => 'xlarge',
                    ],
                    'placeholder' => 'label.pilih.siswa',
                ])
            ;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'kelas' => null,
                'tanggal' => null,
                'kehadiran' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kehadiransiswasms';
    }
}
