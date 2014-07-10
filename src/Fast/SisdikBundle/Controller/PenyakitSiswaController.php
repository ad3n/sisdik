<?php

namespace Langgas\SisdikBundle\Controller;
use Langgas\SisdikBundle\Util\RuteAsal;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\PenyakitSiswa;
use Langgas\SisdikBundle\Form\PenyakitSiswaType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PenyakitSiswa controller.
 *
 * @Route("/{sid}/riwayat-penyakit", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class PenyakitSiswaController extends Controller
{

    /**
     * Lists all PenyakitSiswa entities.
     *
     * @Route("/pendaftar", name="riwayat-penyakit-pendaftar")
     * @Route("/siswa", name="riwayat-penyakit-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('LanggasSisdikBundle:PenyakitSiswa', 't')
                ->where('t.siswa = :siswa')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination,
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Creates a new PenyakitSiswa entity.
     *
     * @Route("/pendaftar", name="riwayat-penyakit-pendaftar_create")
     * @Route("/siswa", name="riwayat-penyakit-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenyakitSiswa:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PenyakitSiswa();
        $form = $this->createForm(new PenyakitSiswaType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.riwayat.penyakit.tersimpan',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'riwayat-penyakit-pendaftar_show'
                                                    : 'riwayat-penyakit-siswa_show',
                                            array(
                                                'sid' => $sid, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to create a new PenyakitSiswa entity.
     *
     * @Route("/pendaftar/new", name="riwayat-penyakit-pendaftar_new")
     * @Route("/siswa/new", name="riwayat-penyakit-siswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new PenyakitSiswa();
        $form = $this->createForm(new PenyakitSiswaType(), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Finds and displays a PenyakitSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="riwayat-penyakit-pendaftar_show")
     * @Route("/siswa/{id}", name="riwayat-penyakit-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to edit an existing PenyakitSiswa entity.
     *
     * @Route("/pendaftar/{id}/edit", name="riwayat-penyakit-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="riwayat-penyakit-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $editForm = $this->createForm(new PenyakitSiswaType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Edits an existing PenyakitSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="riwayat-penyakit-pendaftar_update")
     * @Route("/siswa/{id}", name="riwayat-penyakit-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PenyakitSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PenyakitSiswaType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.riwayat.penyakit.terbarui',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'riwayat-penyakit-pendaftar_edit'
                                                    : 'riwayat-penyakit-siswa_edit',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Deletes a PenyakitSiswa entity.
     *
     * @Route("/pendaftar/{id}/delete", name="riwayat-penyakit-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="riwayat-penyakit-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:PenyakitSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.riwayat.penyakit.terhapus',
                                                array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.riwayat.penyakit.gagal.dihapus',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo())
                                                == 'pendaftar' ? 'riwayat-penyakit-pendaftar'
                                                : 'riwayat-penyakit-siswa',
                                        array(
                                            'sid' => $sid,
                                        )));
    }

    /**
     * Creates a form to delete a PenyakitSiswa entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        if (RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar') {
            $menu[$this->get('translator')->trans('headings.pendaftaran', array(), 'navigations')][$this->get('translator')->trans('links.registration', array(), 'navigations')]->setCurrent(true);
        } else {
            $menu[$this->get('translator')->trans('headings.academic', array(), 'navigations')][$this->get('translator')->trans('links.siswa', array(), 'navigations')]->setCurrent(true);
        }
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
