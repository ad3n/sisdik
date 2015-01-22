<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Kelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/kelas")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_GURU', 'ROLE_GURU_PIKET')")
 */
class KelasController extends Controller
{
    /**
     * @Route("/", name="data_class")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();

        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carikelas');

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->leftJoin('kelas.tahunAkademik', 'tahunAkademik')
            ->where('kelas.sekolah = :sekolah')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('tingkat.urutan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder
                    ->andWhere('kelas.tahunAkademik = :tahunAkademik')
                    ->setParameter('tahunAkademik', $searchdata['tahunAkademik'])
                ;
            }
        }

        $duplicateform = $this->createForm('sisdik_duplikatkelas');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'duplicateform' => $duplicateform->createView(),
        ];
    }

    /**
     * @Route("/{id}/show", name="data_class_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="data_class_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Kelas();
        $form = $this->createForm('sisdik_kelas', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="data_class_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Kelas:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new Kelas();
        $form = $this->createForm('sisdik_kelas', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.class.inserted', [
                        '%class%' => $entity->getNama(),
                        '%year%' => $entity->getTahunAkademik()->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('data_class_show', [
                    'id' => $entity->getId(),
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.class.year.school');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="data_class_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_kelas', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="data_class_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Kelas:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_kelas', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.class.updated', [
                        '%class%' => $entity->getNama(),
                        '%year%' => $entity->getTahunAkademik()->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('data_class_edit', [
                    'id' => $id,
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.class.year.school');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="data_class_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:Kelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.class.deleted', [
                        '%class%' => $entity->getNama(),
                        '%year%' => $entity->getTahunAkademik()->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.data.class.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('data_class'));
    }

    /**
     * Menggandakan kelas-kelas dari satu tahun akademik ke yg lainnya
     *
     * @Route("/duplicate", name="data_class_duplicate")
     * @Method("POST")
     */
    public function duplicateClassAction(Request $request)
    {
        $this->setCurrentMenu();

        $form = $this->createForm('sisdik_duplikatkelas');
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $duplicatedata = $form->getData();

            $tahunAkademikSource = $duplicatedata['tahunAkademikSource'];
            $tahunAkademikTarget = $duplicatedata['tahunAkademikTarget'];

            // get all classes from the source academic year
            $entities = $em->getRepository('LanggasSisdikBundle:Kelas')
                ->findBy([
                    'tahunAkademik' => $tahunAkademikSource,
                ])
            ;

            foreach ($entities as $entity) {
                $kelas = new Kelas();
                $kelas->setTingkat($entity->getTingkat());
                $kelas->setSekolah($entity->getSekolah());
                $kelas->setTahunAkademik($tahunAkademikTarget);
                $kelas->setKeterangan($entity->getKeterangan());
                $kelas->setKode($entity->getKode());
                $kelas->setNama($entity->getNama());
                $kelas->setUrutan($entity->getUrutan());
                try {
                    $em->persist($kelas);
                    $em->flush();
                } catch (DBALException $e) {
                    $exception = $this->get('translator')->trans('exception.unique.class.year.school');
                    throw new DBALException($exception);
                }
            }

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.class.duplicated', [
                    '%yearfrom%' => $tahunAkademikSource->getNama(),
                    '%yearto%' => $tahunAkademikTarget->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.class.fail.duplicate'))
            ;
        }

        return $this->redirect($this->generateUrl('data_class'));
    }

    /**
     * @Route("/ajax/updateclass", name="data_class_ajax_updateclass")
     */
    public function ajaxUpdateclassAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $this->getRequest()->query->get('tahunAkademik');
        $kelas = $this->getRequest()->query->get('kelas');

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('kelas.tahunAkademik = :tahunAkademik')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;
        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            $retval[] = [
                'optionValue' => $result->getId(),
                'optionDisplay' => $result->getNama(),
                'optionSelected' => $kelas == $result->getId() ? 'selected' : '',
            ];
        }

        $return = json_encode($retval);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/ajax/updateclass-bylevel", name="data_class_ajax_updateclass_bylevel")
     */
    public function ajaxUpdateclassByLevelAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $this->getRequest()->query->get('tahunAkademik');
        $tingkat = $this->getRequest()->query->get('tingkat');
        $kelas = $this->getRequest()->query->get('kelas');
        $bolehKosong = $this->getRequest()->query->get('bolehKosong');

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('kelas.tahunAkademik = :tahunAkademik')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;

        if ($tingkat != '') {
            $querybuilder
                ->andWhere('kelas.tingkat = :tingkat')
                ->setParameter('tingkat', $tingkat)
            ;
        }

        $retval = [];
        if ($bolehKosong == 1) {
            $retval[] = [
                'optionValue' => '',
                'optionDisplay' => $this->container->get('translator')->trans('label.seluruh.kelas'),
                'optionSelected' => 'selected',
            ];
        }

        $entities = $querybuilder->getQuery()->getResult();

        foreach ($entities as $entity) {
            $retval[] = [
                'optionValue' => $entity->getId(),
                'optionDisplay' => $entity->getNama(),
                'optionSelected' => $kelas == $entity->getId() ? 'selected' : '',
            ];
        }

        $return = json_encode($retval);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/ajax/updateclass/schooldefined/{sekolah}", name="data_class_ajax_updateclass_schooldefined")
     */
    public function ajaxUpdateclassSchoolDefinedAction(Request $request, $sekolah)
    {
        $em = $this->getDoctrine()->getManager();

        $tahunAkademik = $this->getRequest()->query->get('tahunAkademik');
        $kelas = $this->getRequest()->query->get('kelas');

        $querybuilder = $em->createQueryBuilder()
            ->select('kelas')
            ->from('LanggasSisdikBundle:Kelas', 'kelas')
            ->leftJoin('kelas.tingkat', 'tingkat')
            ->where('kelas.sekolah = :sekolah')
            ->andWhere('kelas.tahunAkademik = :tahunAkademik')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('kelas.urutan')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('tahunAkademik', $tahunAkademik)
        ;
        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            $retval[] = [
                'optionValue' => $result->getId(),
                'optionDisplay' => $result->getNama(),
                'optionSelected' => $kelas == $result->getId() ? 'selected' : '',
            ];
        }

        $return = json_encode($retval);

        return new Response($return, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id,
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.data.class', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
