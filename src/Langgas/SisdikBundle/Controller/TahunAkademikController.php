<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/tahun-akademik")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class TahunAkademikController extends Controller
{
    /**
     * @Route("/", name="academicyear")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            $querybuilder = $em->createQueryBuilder()
                ->select('tahunakademik')
                ->from('LanggasSisdikBundle:TahunAkademik', 'tahunakademik')
                ->where('tahunakademik.sekolah = :sekolah')
                ->orderBy('tahunakademik.urutan', 'DESC')
                ->addOrderBy('tahunakademik.nama', 'DESC')
                ->setParameter('sekolah', $sekolah)
            ;
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * Mengaktifkan satu tahun akademik dan menon-aktifkan yang lainnya
     *
     * @Route("/{id}/activate", name="academicyear_activate")
     */
    public function activateAction($id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TahunAkademik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TahunAkademik tak ditemukan.');
        }

        $em
            ->createQueryBuilder()
            ->update('LanggasSisdikBundle:TahunAkademik', 'tahunakademik')
            ->set('tahunakademik.aktif', '0')
            ->where('tahunakademik.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->getQuery()
            ->execute()
        ;

        $entity->setAktif(1);
        $em->persist($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('academicyear'));
    }

    /**
     * @Route("/{id}/show", name="academicyear_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TahunAkademik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TahunAkademik tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="academicyear_new")
     * @Template()
     */
    public function newAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new TahunAkademik();

        $qbe = $em->createQueryBuilder();
        $queryUrutan = $em->createQueryBuilder()
            ->select($qbe->expr()->max('tahunAkademik.urutan'))
            ->from('LanggasSisdikBundle:TahunAkademik', 'tahunAkademik')
            ->where('tahunAkademik.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;
        $nomorUrut = $queryUrutan->getQuery()->getSingleScalarResult();
        $nomorUrut = $nomorUrut === null ? 0 : $nomorUrut;
        $nomorUrut++;

        $entity->setUrutan($nomorUrut);

        $form = $this->createForm('sisdik_tahunakademik', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="academicyear_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:TahunAkademik:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new TahunAkademik();
        $form = $this->createForm('sisdik_tahunakademik', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.academicyear.inserted', [
                        '%year%' => $entity->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('academicyear_show', [
                    'id' => $entity->getId(),
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.year.school');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="academicyear_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TahunAkademik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TahunAkademik tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_tahunakademik', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="academicyear_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:TahunAkademik:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TahunAkademik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TahunAkademik tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_tahunakademik', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.academicyear.updated', [
                        '%year%' => $entity->getNama(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('academicyear_edit', [
                    'id' => $id,
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.year.school');
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
     * @Route("/{id}/delete", name="academicyear_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:TahunAkademik')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity TahunAkademik tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.academicyear.deleted', [
                        '%year%' => $entity->getNama(),
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
                ->add('error', $this->get('translator')->trans('flash.data.academicyear.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('academicyear'));
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
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.academicyear', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
