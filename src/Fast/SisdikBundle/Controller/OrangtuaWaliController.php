<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Form\OrangtuaWaliType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * OrangtuaWali controller.
 *
 * @Route("/{sid}/parentguard", requirements={"siswa"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class OrangtuaWaliController extends Controller
{
    /**
     * Lists all OrangtuaWali entities.
     *
     * @Route("/", name="parentguard")
     * @Template()
     */
    public function indexAction($sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:OrangtuaWali', 't')
                ->where('t.siswa = :siswa')->orderBy('t.aktif', 'ASC')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Finds and displays a OrangtuaWali entity.
     *
     * @Route("/{id}/show", name="parentguard_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new OrangtuaWali entity.
     *
     * @Route("/new", name="parentguard_new")
     * @Template()
     */
    public function newAction($sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new OrangtuaWali entity.
     *
     * @Route("/create", name="parentguard_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('parentguard_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing OrangtuaWali entity.
     *
     * @Route("/{id}/edit", name="parentguard_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
        }

        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Edits an existing OrangtuaWali entity.
     *
     * @Route("/{id}/update", name="parentguard_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('parentguard_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a OrangtuaWali entity.
     *
     * @Route("/{id}/delete", name="parentguard_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('parentguard'));
    }

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
        $user = $this->container->get('security.context')->getToken()->getUser();
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
