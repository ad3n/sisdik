<?php

namespace Fast\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\JenisImbalan;
use Fast\SisdikBundle\Form\JenisImbalanType;

/**
 * JenisImbalan controller.
 *
 * @Route("/rewardtype")
 */
class JenisImbalanController extends Controller
{
    /**
     * Lists all JenisImbalan entities.
     *
     * @Route("/", name="rewardtype")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FastSisdikBundle:JenisImbalan')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a JenisImbalan entity.
     *
     * @Route("/{id}/show", name="rewardtype_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JenisImbalan entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new JenisImbalan entity.
     *
     * @Route("/new", name="rewardtype_new")
     * @Template()
     */
    public function newAction()
    {
        $entity = new JenisImbalan();
        $form   = $this->createForm(new JenisImbalanType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new JenisImbalan entity.
     *
     * @Route("/create", name="rewardtype_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisImbalan:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new JenisImbalan();
        $form = $this->createForm(new JenisImbalanType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('rewardtype_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing JenisImbalan entity.
     *
     * @Route("/{id}/edit", name="rewardtype_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JenisImbalan entity.');
        }

        $editForm = $this->createForm(new JenisImbalanType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing JenisImbalan entity.
     *
     * @Route("/{id}/update", name="rewardtype_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:JenisImbalan:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find JenisImbalan entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JenisImbalanType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('rewardtype_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a JenisImbalan entity.
     *
     * @Route("/{id}/delete", name="rewardtype_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JenisImbalan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find JenisImbalan entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('rewardtype'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
