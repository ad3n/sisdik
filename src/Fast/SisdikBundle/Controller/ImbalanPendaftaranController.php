<?php

namespace Fast\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\ImbalanPendaftaran;
use Fast\SisdikBundle\Form\ImbalanPendaftaranType;

/**
 * ImbalanPendaftaran controller.
 *
 * @Route("/rewardamount")
 */
class ImbalanPendaftaranController extends Controller
{
    /**
     * Lists all ImbalanPendaftaran entities.
     *
     * @Route("/", name="rewardamount")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FastSisdikBundle:ImbalanPendaftaran')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a ImbalanPendaftaran entity.
     *
     * @Route("/{id}/show", name="rewardamount_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ImbalanPendaftaran entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new ImbalanPendaftaran entity.
     *
     * @Route("/new", name="rewardamount_new")
     * @Template()
     */
    public function newAction()
    {
        $entity = new ImbalanPendaftaran();
        $form   = $this->createForm(new ImbalanPendaftaranType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new ImbalanPendaftaran entity.
     *
     * @Route("/create", name="rewardamount_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:ImbalanPendaftaran:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new ImbalanPendaftaran();
        $form = $this->createForm(new ImbalanPendaftaranType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('rewardamount_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing ImbalanPendaftaran entity.
     *
     * @Route("/{id}/edit", name="rewardamount_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ImbalanPendaftaran entity.');
        }

        $editForm = $this->createForm(new ImbalanPendaftaranType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing ImbalanPendaftaran entity.
     *
     * @Route("/{id}/update", name="rewardamount_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:ImbalanPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:ImbalanPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ImbalanPendaftaran entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ImbalanPendaftaranType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('rewardamount_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a ImbalanPendaftaran entity.
     *
     * @Route("/{id}/delete", name="rewardamount_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:ImbalanPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find ImbalanPendaftaran entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('rewardamount'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
