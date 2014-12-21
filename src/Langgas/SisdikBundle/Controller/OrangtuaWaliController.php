<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Util\RuteAsal;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/{sid}/ortuwali", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class OrangtuaWaliController extends Controller
{
    /**
     * @Route("/pendaftar", name="ortuwali-pendaftar")
     * @Route("/siswa", name="ortuwali-siswa")
     * @Template()
     */
    public function indexAction($sid)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('orangtuaWali')
            ->from('LanggasSisdikBundle:OrangtuaWali', 'orangtuaWali')
            ->where('orangtuaWali.siswa = :siswa')
            ->orderBy('orangtuaWali.aktif', 'DESC')
            ->setParameter('siswa', $sid)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * Mengaktifkan status orang tua wali, dan menonaktifkan yang lain
     *
     * @Route("/pendaftar/{id}/activate", name="ortuwali-pendaftar_activate")
     * @Route("/siswa/{id}/activate", name="ortuwali-siswa_activate")
     */
    public function activateAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $em
            ->createQueryBuilder()
            ->update('LanggasSisdikBundle:OrangtuaWali', 'orangtuaWali')
            ->set('orangtuaWali.aktif', 0)
            ->where('orangtuaWali.siswa = :siswa')
            ->setParameter('siswa', $sid)
            ->getQuery()
            ->execute()
        ;

        $entity->setAktif(true);
        $em->persist($entity);
        $em->flush();

        return $this->redirect($this->generateUrl(
            RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar' : 'ortuwali-siswa',
            ['sid' => $sid,]
        ));
    }

    /**
     * @Route("/pendaftar/new", name="ortuwali-pendaftar_new")
     * @Route("/siswa/new", name="ortuwali-siswa_new")
     * @Template()
     */
    public function newAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $entity->setSiswa($em->getRepository('LanggasSisdikBundle:Siswa')->find($sid));

        $form = $this->createForm('sisdik_orangtuawali', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/create", name="ortuwali-pendaftar_create")
     * @Route("/siswa/create", name="ortuwali-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request, $sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $entity->setSiswa($em->getRepository('LanggasSisdikBundle:Siswa')->find($sid));

        $form = $this->createForm('sisdik_orangtuawali', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $entity->setAktif(false);

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.ortuwali.tersimpan', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar_show' : 'ortuwali-siswa_show',
                [
                    'sid' => $sid,
                    'id' => $entity->getId(),
                ]
            ));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/show", name="ortuwali-pendaftar_show")
     * @Route("/siswa/{id}/show", name="ortuwali-siswa_show")
     * @Template()
     */
    public function showAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/edit", name="ortuwali-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="ortuwali-siswa_edit")
     * @Template()
     */
    public function editAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_orangtuawali', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/update", name="ortuwali-pendaftar_update")
     * @Route("/siswa/{id}/update", name="ortuwali-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_orangtuawali', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.ortuwali.terbarui', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar_edit' : 'ortuwali-siswa_edit',
                [
                    'sid' => $sid,
                    'id' => $id,
                ]
            ));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/delete", name="ortuwali-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="ortuwali-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
            }

            try {
                if ($entity->isAktif() === true) {
                    $message = $this->get('translator')->trans('exception.larangan.hapus.ortuwali');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.ortuwali.terhapus', [
                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
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
                ->add('error', $this->get('translator')->trans('flash.ortuwali.gagal.dihapus', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl(
            RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar' : 'ortuwali-siswa',
            ['sid' => $sid,]
        ));
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

        if (RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar') {
            $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.registration', [], 'navigations')]->setCurrent(true);
        } else {
            $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.siswa', [], 'navigations')]->setCurrent(true);
        }
    }
}
