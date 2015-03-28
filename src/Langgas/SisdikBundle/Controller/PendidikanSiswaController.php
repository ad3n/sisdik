<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PendidikanSiswa;
use Langgas\SisdikBundle\Util\RuteAsal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/{sid}/pendidikan-sebelumnya", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class PendidikanSiswaController extends Controller
{
    /**
     * @Route("/pendaftar", name="pendidikan-sebelumnya-pendaftar")
     * @Route("/siswa", name="pendidikan-sebelumnya-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('view', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('pendidikanSiswa')
            ->from('LanggasSisdikBundle:PendidikanSiswa', 'pendidikanSiswa')
            ->where('pendidikanSiswa.siswa = :siswa')
            ->orderBy('pendidikanSiswa.jenjang', 'DESC')
            ->setParameter('siswa', $sid)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/new", name="pendidikan-sebelumnya-pendaftar_new")
     * @Route("/siswa/new", name="pendidikan-sebelumnya-siswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('create', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new PendidikanSiswa();
        $form = $this->createForm('sisdik_pendidikansiswa', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar", name="pendidikan-sebelumnya-pendaftar_create")
     * @Route("/siswa", name="pendidikan-sebelumnya-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PendidikanSiswa:new.html.twig")
     */
    public function createAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('create', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new PendidikanSiswa();
        $form = $this->createForm('sisdik_pendidikansiswa', $entity);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.pendidikan.sebelumnya.tersimpan', [
                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                        '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar_show' : 'pendidikan-sebelumnya-siswa_show',
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
            'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}", name="pendidikan-sebelumnya-pendaftar_show")
     * @Route("/siswa/{id}", name="pendidikan-sebelumnya-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('view', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/edit", name="pendidikan-sebelumnya-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="pendidikan-sebelumnya-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('edit', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_pendidikansiswa', $entity);
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
     * @Route("/pendaftar/{id}", name="pendidikan-sebelumnya-pendaftar_update")
     * @Route("/siswa/{id}", name="pendidikan-sebelumnya-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PendidikanSiswa:edit.html.twig")
     */
    public function updateAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('edit', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_pendidikansiswa', $entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            // force changes
            if ($editForm->get('fileUploadIjazah')->getData() !== null) {
                $entity->setIjazahFile(uniqid());
            }
            if ($editForm->get('fileUploadKelulusan')->getData() !== null) {
                $entity->setKelulusanFile(uniqid());
            }

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.pendidikan.sebelumnya.terbarui', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                    '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl(
                RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar_edit' : 'pendidikan-sebelumnya-siswa_edit',
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
     * @Route("/pendaftar/{id}/delete", name="pendidikan-sebelumnya-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="pendidikan-sebelumnya-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction($sid, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($this->get('security.authorization_checker')->isGranted('delete', $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid)) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $entity = $em->getRepository('LanggasSisdikBundle:PendidikanSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.pendidikan.sebelumnya.terhapus', [
                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                        '%nama%' => $entity->getNama(),
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
                ->add('error', $this->get('translator')->trans('flash.pendidikan.sebelumnya.gagal.dihapus', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                    '%nama%' => $entity->getNama(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl(
            RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar' : 'pendidikan-sebelumnya-siswa',
            [
                'sid' => $sid,
            ]
        ));
    }

    /**
     * @param mixed $id
     *
     * @return Symfony\Component\Form\Form The form
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
}
