<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PenyakitSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Util\RuteAsal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/{sid}/riwayat-penyakit", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class PenyakitSiswaController extends Controller
{
    /**
     * @Route("/pendaftar", name="riwayat-penyakit-pendaftar")
     * @Route("/siswa", name="riwayat-penyakit-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('view', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('penyakitSiswa')
            ->from('LanggasSisdikBundle:PenyakitSiswa', 'penyakitSiswa')
            ->where('penyakitSiswa.siswa = :siswa')
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
     * @Route("/pendaftar", name="riwayat-penyakit-pendaftar_create")
     * @Route("/siswa", name="riwayat-penyakit-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenyakitSiswa:new.html.twig")
     */
    public function createAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('create', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new PenyakitSiswa();
        $form = $this->createForm('sisdik_penyakitsiswa', $entity);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.riwayat.penyakit.tersimpan', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ?
                    'riwayat-penyakit-pendaftar_show' : 'riwayat-penyakit-siswa_show',
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
     * @Route("/pendaftar/new", name="riwayat-penyakit-pendaftar_new")
     * @Route("/siswa/new", name="riwayat-penyakit-siswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('create', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new PenyakitSiswa();
        $form = $this->createForm('sisdik_penyakitsiswa', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}", name="riwayat-penyakit-pendaftar_show")
     * @Route("/siswa/{id}", name="riwayat-penyakit-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('view', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/edit", name="riwayat-penyakit-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="riwayat-penyakit-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('edit', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_penyakitsiswa', $entity);
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
     * @Route("/pendaftar/{id}", name="riwayat-penyakit-pendaftar_update")
     * @Route("/siswa/{id}", name="riwayat-penyakit-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenyakitSiswa:edit.html.twig")
     */
    public function updateAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.context')->isGranted('edit', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_penyakitsiswa', $entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.riwayat.penyakit.terbarui', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar(
                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'riwayat-penyakit-pendaftar_edit' : 'riwayat-penyakit-siswa_edit',
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
     * @Route("/pendaftar/{id}/delete", name="riwayat-penyakit-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="riwayat-penyakit-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction($sid, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($this->get('security.context')->isGranted('delete', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.riwayat.penyakit.terhapus', [
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
                ->add('error', $this->get('translator')->trans('flash.riwayat.penyakit.gagal.dihapus', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl(
            RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'riwayat-penyakit-pendaftar' : 'riwayat-penyakit-siswa',
            [
                'sid' => $sid,
            ]
        ));
    }

    /**
     * @param mixed $id
     *
     * @return Form
     */
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

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
