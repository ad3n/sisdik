<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Fast\SisdikBundle\Entity\Sekolah;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PenyakitSiswa;
use Fast\SisdikBundle\Form\PenyakitSiswaType;
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
     * @Route("/", name="riwayat-penyakit")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PenyakitSiswa', 't')
                ->where('t.siswa = :siswa')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Displays a form to create a new PenyakitSiswa entity.
     *
     * @Route("/new", name="riwayat-penyakit_new")
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
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Creates a new PenyakitSiswa entity.
     *
     * @Route("/", name="riwayat-penyakit_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PenyakitSiswa:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PenyakitSiswa();
        $form = $this->createForm(new PenyakitSiswaType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
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
                                    ->generateUrl('riwayat-penyakit_show',
                                            array(
                                                'sid' => $sid, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Finds and displays a PenyakitSiswa entity.
     *
     * @Route("/{id}", name="riwayat-penyakit_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing PenyakitSiswa entity.
     *
     * @Route("/{id}/edit", name="riwayat-penyakit_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $editForm = $this->createForm(new PenyakitSiswaType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
        );
    }

    /**
     * Edits an existing PenyakitSiswa entity.
     *
     * @Route("/{id}", name="riwayat-penyakit_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PenyakitSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PenyakitSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PenyakitSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new PenyakitSiswaType(), $entity);
        $editForm->bind($request);

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
                                    ->generateUrl('riwayat-penyakit_edit',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
        );
    }

    /**
     * Deletes a PenyakitSiswa entity.
     *
     * @Route("/{id}/delete", name="riwayat-penyakit_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:PenyakitSiswa')->find($id);

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
                                ->generateUrl('riwayat-penyakit',
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
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.registration']->setCurrent(true);
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
