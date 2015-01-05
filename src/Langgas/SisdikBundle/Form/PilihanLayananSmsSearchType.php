<?php

namespace Langgas\SisdikBundle\Form;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JMS\DiExtraBundle\Annotation\FormType;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;

/**
 * @FormType
 */
class PilihanLayananSmsSearchType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @InjectParams({
     *     "entityManager" = @Inject("doctrine.orm.entity_manager")
     * })
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sekolah', 'choice', [
                'choices' => $this->buildSchoolChoices(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
            ->add('jenisLayanan', 'choice', [
                'choices' => $this->buildServiceChoices(),
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'attr' => [
                    'class' => 'large',
                ],
                'label_render' => false,
                'horizontal' => false,
            ])
        ;
    }

    private function buildSchoolChoices()
    {
        $entities = $this->entityManager->getRepository('LanggasSisdikBundle:Sekolah')
            ->findBy([], [
                'nama' => 'ASC'
            ])
        ;

        $choices = [
            '' => 'label.allschool',
        ];

        foreach ($entities as $entity) {
            if ($entity instanceof Sekolah) {
                $choices[$entity->getId()] = $entity->getNama();
            }
        }

        return $choices;
    }

    private function buildServiceChoices()
    {
        $choices = array_merge(
            ['' => 'label.semua.layanan'],
            PilihanLayananSms::getDaftarLayananPendaftaran(),
            PilihanLayananSms::getDaftarLayananLaporan(),
            PilihanLayananSms::getDaftarLayananKehadiran(),
            PilihanLayananSms::getDaftarLayananKepulangan(),
            PilihanLayananSms::getDaftarLayananBiayaSekaliBayar(),
            PilihanLayananSms::getDaftarLayananLain()
        );

        return $choices;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
            ])
        ;
    }

    public function getName()
    {
        return 'sisdik_carilayanansms';
    }
}
