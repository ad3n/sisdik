<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Util\RuteAsal;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PendidikanSiswa;
use Fast\SisdikBundle\Form\PendidikanSiswaType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PendidikanSiswa controller.
 *
 * @Route("/{sid}/pendidikan-sebelumnya", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class PendidikanSiswaController extends Controller
{
    /**
     * Lists all PendidikanSiswa entities.
     *
     * @Route("/pendaftar", name="pendidikan-sebelumnya-pendaftar")
     * @Route("/siswa", name="pendidikan-sebelumnya-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PendidikanSiswa', 't')
                ->where('t.siswa = :siswa')->orderBy('t.jenjang', 'ASC')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination,
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to create a new PendidikanSiswa entity.
     *
     * @Route("/pendaftar/new", name="pendidikan-sebelumnya-pendaftar_new")
     * @Route("/siswa/new", name="pendidikan-sebelumnya-siswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new PendidikanSiswa();
        $form = $this->createForm(new PendidikanSiswaType(), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Creates a new PendidikanSiswa entity.
     *
     * @Route("/pendaftar", name="pendidikan-sebelumnya-pendaftar_create")
     * @Route("/siswa", name="pendidikan-sebelumnya-siswa_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PendidikanSiswa:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new PendidikanSiswa();
        $form = $this->createForm(new PendidikanSiswaType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.pendidikan.sebelumnya.tersimpan',
                                            array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                    '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar_show'
                                                    : 'pendidikan-sebelumnya-siswa_show',
                                            array(
                                                'sid' => $sid, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Finds and displays a PendidikanSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="pendidikan-sebelumnya-pendaftar_show")
     * @Route("/siswa/{id}", name="pendidikan-sebelumnya-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'daftarPilihanJenjangSekolah' => PendidikanSiswa::daftarPilihanJenjangSekolah(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to edit an existing PendidikanSiswa entity.
     *
     * @Route("/pendaftar/{id}/edit", name="pendidikan-sebelumnya-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="pendidikan-sebelumnya-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $editForm = $this->createForm(new PendidikanSiswaType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Edits an existing PendidikanSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="pendidikan-sebelumnya-pendaftar_update")
     * @Route("/siswa/{id}", name="pendidikan-sebelumnya-siswa_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PendidikanSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PendidikanSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PendidikanSiswaType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            // force changes
            if ($editForm->get('fileUploadIjazah')->getData() !== null) {
                $entity->setIjazahFile(uniqid());
            }
            if ($editForm->get('fileUploadSttb')->getData() !== null) {
                $entity->setSttbFile(uniqid());
            }

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.pendidikan.sebelumnya.terbarui',
                                            array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                    '%nama%' => $entity->getNama()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            RuteAsal::ruteAsalSiswaPendaftar(
                                                    $this->getRequest()->getPathInfo()) == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar_edit'
                                                    : 'pendidikan-sebelumnya-siswa_edit',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Deletes a PendidikanSiswa entity.
     *
     * @Route("/pendaftar/{id}/delete", name="pendidikan-sebelumnya-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="pendidikan-sebelumnya-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:PendidikanSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PendidikanSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.pendidikan.sebelumnya.terhapus',
                                                array(
                                                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                        '%nama%' => $entity->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.pendidikan.sebelumnya.gagal.dihapus',
                                            array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                    '%nama%' => $entity->getNama()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo())
                                                == 'pendaftar' ? 'pendidikan-sebelumnya-pendaftar'
                                                : 'pendidikan-sebelumnya-siswa',
                                        array(
                                            'sid' => $sid,
                                        )));
    }

    /**
     * Creates a form to delete a PendidikanSiswa entity by id.
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
        $menu = $this->container->get('fast_sisdik.menu.main');
        if (RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar') {
            $menu['headings.pendaftaran']['links.registration']->setCurrent(true);
        } else {
            $menu['headings.academic']['links.siswa']->setCurrent(true);
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
