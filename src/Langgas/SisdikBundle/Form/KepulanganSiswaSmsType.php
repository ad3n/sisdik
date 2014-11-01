<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityRepository;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\KepulanganSiswa;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;

/**
 * @FormType
 */
class KepulanganSiswaSmsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statusKepulangan', 'choice', [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => JadwalKepulangan::getDaftarStatusKepulangan(),
                'attr' => [
                    'class' => 'medium',
                ],
                'empty_value' => 'label.pilih.status.kepulangan',
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
        foreach ($options['kepulangan'] as $kepulangan) {
            if ($kepulangan instanceof KepulanganSiswa) {
                $siswa[] = $kepulangan->getSiswa()->getId();
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
                    'empty_value' => 'label.pilih.siswa',
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
                'kepulangan' => null,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_kepulangansiswasms';
    }
}
