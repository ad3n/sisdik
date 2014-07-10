<?php
namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\LayananSmsPendaftaran;
use Langgas\SisdikBundle\Form\LayananSmsPendaftaranType;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * LayananSmsPendaftaran controller.
 *
 * @Route("/smspendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN')")
 */
class LayananSmsPendaftaranController extends Controller
{

    /**
     * Lists all LayananSmsPendaftaran entities.
     *
     * @Route("/", name="smspendaftaran")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:LayananSmsPendaftaran', 't')
            ->where('t.sekolah = :sekolah')
            ->orderBy('t.jenisLayanan', 'ASC')
            ->setParameter('sekolah', $sekolah->getId());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination,
            'daftarJenisLayanan' => PilihanLayananSms::getDaftarLayananPendaftaran()
        );
    }

    /**
     * Displays a form to create a new LayananSmsPendaftaran entity.
     *
     * @Route("/new", name="smspendaftaran_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new LayananSmsPendaftaran();
        $form = $this->createForm(new LayananSmsPendaftaranType($this->container), $entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }

    /**
     * Creates a new LayananSmsPendaftaran entity.
     *
     * @Route("/create", name="smspendaftaran_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:LayananSmsPendaftaran:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new LayananSmsPendaftaran();
        $form = $this->createForm(new LayananSmsPendaftaranType($this->container), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')
                    ->trans('flash.smspendaftaran.tersimpan'));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.smspendaftaran');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('smspendaftaran_show', array(
                'id' => $entity->getId()
            )));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }

    /**
     * Finds and displays a LayananSmsPendaftaran entity.
     *
     * @Route("/{id}", name="smspendaftaran_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')->find($id);

        if (! $entity) {
            throw $this->createNotFoundException('Entity LayananSmsPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarJenisLayanan' => PilihanLayananSms::getDaftarLayananPendaftaran()
        );
    }

    /**
     * Displays a form to edit an existing LayananSmsPendaftaran entity.
     *
     * @Route("/{id}/edit", name="smspendaftaran_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')->find($id);

        if (! $entity) {
            throw $this->createNotFoundException('Entity LayananSmsPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm(new LayananSmsPendaftaranType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Edits an existing LayananSmsPendaftaran entity.
     *
     * @Route("/{id}/update", name="smspendaftaran_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:LayananSmsPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')->find($id);

        if (! $entity) {
            throw $this->createNotFoundException('Entity LayananSmsPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new LayananSmsPendaftaranType($this->container), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')
                    ->trans('flash.smspendaftaran.terbarui'));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.smspendaftaran');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('smspendaftaran_edit', array(
                'id' => $id
            )));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Deletes a LayananSmsPendaftaran entity.
     *
     * @Route("/{id}/delete", name="smspendaftaran_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPendaftaran')->find($id);

            if (! $entity) {
                throw $this->createNotFoundException('Entity LayananSmsPendaftaran tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')
                ->trans('flash.smspendaftaran.terhapus'));
        } else {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')
                ->trans('flash.smspendaftaran.gagal.dihapus'));
        }

        return $this->redirect($this->generateUrl('smspendaftaran'));
    }

    /**
     * Creates a form to delete a LayananSmsPendaftaran entity by id.
     *
     * @param mixed $id
     *            The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array(
            'id' => $id
        ))
            ->add('id', 'hidden')
            ->getForm();
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.setting', array(), 'navigations')][$this->get('translator')->trans('links.smspendaftaran', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool()
    {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else
            if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
                throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
            } else {
                throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
            }
    }
}
