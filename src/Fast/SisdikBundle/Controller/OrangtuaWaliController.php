<?php

namespace Fast\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Form\OrangtuaWaliType;

/**
 * OrangtuaWali controller.
 *
 * @Route("/appguardian")
 */
class OrangtuaWaliController extends Controller
{
    /**
     * Lists all OrangtuaWali entities.
     *
     * @Route("/", name="appguardian")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FastSisdikBundle:OrangtuaWali')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a OrangtuaWali entity.
     *
     * @Route("/{id}/show", name="appguardian_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new OrangtuaWali entity.
     *
     * @Route("/new", name="appguardian_new")
     * @Template()
     */
    public function newAction()
    {
        $entity = new OrangtuaWali();
        $form   = $this->createForm(new OrangtuaWaliType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new OrangtuaWali entity.
     *
     * @Route("/create", name="appguardian_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('appguardian_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing OrangtuaWali entity.
     *
     * @Route("/{id}/edit", name="appguardian_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find OrangtuaWali entity.');
        }

        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing OrangtuaWali entity.
     *
     * @Route("/{id}/update", name="appguardian_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
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

            return $this->redirect($this->generateUrl('appguardian_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a OrangtuaWali entity.
     *
     * @Route("/{id}/delete", name="appguardian_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
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

        return $this->redirect($this->generateUrl('appguardian'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
