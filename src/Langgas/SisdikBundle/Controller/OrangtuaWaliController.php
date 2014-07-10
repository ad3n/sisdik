<?php

namespace Langgas\SisdikBundle\Controller;
use Langgas\SisdikBundle\Util\RuteAsal;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Form\OrangtuaWaliType;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * OrangtuaWali controller.
 *
 * @Route("/{sid}/ortuwali", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class OrangtuaWaliController extends Controller
{
    /**
     * Lists all OrangtuaWali entities.
     *
     * @Route("/pendaftar", name="ortuwali-pendaftar")
     * @Route("/siswa", name="ortuwali-siswa")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('LanggasSisdikBundle:OrangtuaWali', 't')
                ->where('t.siswa = :siswa')->orderBy('t.aktif', 'DESC')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination,
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Mengaktifkan status orang tua wali, dan menonaktifkan yang lain
     *
     * @Route("/pendaftar/{id}/activate", name="ortuwali-pendaftar_activate")
     * @Route("/siswa/{id}/activate", name="ortuwali-siswa_activate")
     */
    public function activateAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $query = $em->createQueryBuilder()->update('LanggasSisdikBundle:OrangtuaWali', 't')->set('t.aktif', 0)
                ->where('t.siswa = :siswa')->setParameter('siswa', $sid)->getQuery();
        $query->execute();

        $entity->setAktif(true);
        $em->persist($entity);
        $em->flush();

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo())
                                                == 'pendaftar' ? 'ortuwali-pendaftar' : 'ortuwali-siswa',
                                        array(
                                            'sid' => $sid
                                        )));
    }

    /**
     * Displays a form to create a new OrangtuaWali entity.
     *
     * @Route("/pendaftar/new", name="ortuwali-pendaftar_new")
     * @Route("/siswa/new", name="ortuwali-siswa_new")
     * @Template()
     */
    public function newAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Creates a new OrangtuaWali entity.
     *
     * @Route("/pendaftar/create", name="ortuwali-pendaftar_create")
     * @Route("/siswa/create", name="ortuwali-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);
            $entity->setAktif(false);

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.tersimpan',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar_show'
                                                    : 'ortuwali-siswa_show',
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
     * Finds and displays a OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/show", name="ortuwali-pendaftar_show")
     * @Route("/siswa/{id}/show", name="ortuwali-siswa_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to edit an existing OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/edit", name="ortuwali-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="ortuwali-siswa_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Edits an existing OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/update", name="ortuwali-pendaftar_update")
     * @Route("/siswa/{id}/update", name="ortuwali-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.terbarui',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'ortuwali-pendaftar_edit'
                                                    : 'ortuwali-siswa_edit',
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
     * Deletes a OrangtuaWali entity.
     *
     * @Route("/pendaftar/{id}/delete", name="ortuwali-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="ortuwali-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();

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

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.ortuwali.terhapus',
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
                                    ->trans('flash.ortuwali.gagal.dihapus',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo())
                                                == 'pendaftar' ? 'ortuwali-pendaftar' : 'ortuwali-siswa',
                                        array(
                                            'sid' => $sid,
                                        )));
    }

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
