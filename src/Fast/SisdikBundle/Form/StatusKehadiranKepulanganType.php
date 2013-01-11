<?php

namespace Fast\SisdikBundle\Form;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StatusKehadiranKepulanganType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('nama', 'choice',
                        array(
                                'required' => true,
                                'attr' => array(
                                    'class' => 'large'
                                ), 'choices' => $this->buildNamaStatusKehadiranKepulangan()
                        ))
                ->add('keterangan', null,
                        array(
                                'attr' => array(
                                    'class' => 'xlarge'
                                )
                        ));

        $builder
                ->add('sekolah', 'entity',
                        array(
                                'class' => 'FastSisdikBundle:Sekolah', 'label' => 'label.school',
                                'multiple' => false, 'expanded' => false, 'property' => 'nama',
                                'required' => true,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
                ->setDefaults(
                        array(
                            'data_class' => 'Fast\SisdikBundle\Entity\StatusKehadiranKepulangan'
                        ));
    }

    static function buildNamaStatusKehadiranKepulangan() {
        $array = array(
                'hadir-tepat' => 'hadir-tepat', 'hadir-telat' => 'hadir-telat',
                'pulang' => 'pulang', 'pulang-belumfp' => 'pulang-belumfp',
                'pulang-takfp' => 'pulang-takfp', 'alpa' => 'alpa', 'izin' => 'izin',
                'sakit' => 'sakit'
        );
        asort($array);

        return $array;
    }

    static function buildNamaStatusKehadiranSaja() {
        $array = array(
                'hadir-tepat' => 'hadir-tepat', 'hadir-telat' => 'hadir-telat', 'alpa' => 'alpa',
                'izin' => 'izin', 'sakit' => 'sakit'
        );
        asort($array);

        return $array;
    }

    static function buildNamaStatusKepulanganSaja() {
        $array = array(
                'pulang' => 'pulang', 'pulang-belumfp' => 'pulang-belumfp',
                'pulang-takfp' => 'pulang-takfp',
        );
        asort($array);

        return $array;
    }

    public function getName() {
        return 'fast_sisdikbundle_statuskehadirankepulangantype';
    }
}
