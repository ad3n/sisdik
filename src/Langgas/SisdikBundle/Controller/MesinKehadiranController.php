<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\MesinKehadiran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/mesin-kehadiran")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class MesinKehadiranController extends Controller
{
    /**
     * @Route("/", name="attendancemachine")
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
                ->select('mesinKehadiran')
                ->from('LanggasSisdikBundle:MesinKehadiran', 'mesinKehadiran')
                ->where('mesinKehadiran.sekolah = :sekolah')
                ->orderBy('mesinKehadiran.alamatIp', 'ASC')
                ->setParameter('sekolah', $sekolah)
            ;
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $tokenSekolah = $em->getRepository('LanggasSisdikBundle:TokenSekolah')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        $mesinWakil = $em->getRepository('LanggasSisdikBundle:MesinWakil')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        return [
            'pagination' => $pagination,
            'tokenSekolah' => $tokenSekolah,
            'mesinWakil' => $mesinWakil,
        ];
    }

    /**
     * @Route("/{id}/show", name="attendancemachine_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="attendancemachine_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new MesinKehadiran;
        $form = $this->createForm('sisdik_mesinkehadiran', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="attendancemachine_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:MesinKehadiran:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new MesinKehadiran;
        $form = $this->createForm('sisdik_mesinkehadiran', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.attendancemachine.inserted', [
                    '%ip%' => $entity->getAlamatIp(),
                ]))
            ;

            return $this->redirect($this->generateUrl('attendancemachine_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="attendancemachine_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_mesinkehadiran', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="attendancemachine_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:MesinKehadiran:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_mesinkehadiran', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.attendancemachine.updated', [
                    '%ip%' => $entity->getAlamatIp(),
                ]))
            ;

            return $this->redirect($this->generateUrl('attendancemachine_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="attendancemachine_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:MesinKehadiran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity MesinKehadiran tak ditemukan.');
            }

            if ($this->get('security.context')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.attendancemachine.deleted', [
                    '%ip%' => $entity->getAlamatIp(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.attendancemachine.fail.delete', [
                    '%ip%' => $entity->getAlamatIp(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('attendancemachine'));
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
        $menu[$translator->trans('headings.presence', [], 'navigations')][$translator->trans('links.attendancemachine', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
