<?php

namespace Fast\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\CalonTransaksiPembayaranSekali;
use Fast\SisdikBundle\Form\CalonTransaksiPembayaranSekaliType;

/**
 * CalonTransaksiPembayaranSekali controller.
 *
 * @Route("/applicant_oncefee_transaction")
 */
class CalonTransaksiPembayaranSekaliController extends Controller
{
    /**
     * Lists all CalonTransaksiPembayaranSekali entities.
     *
     * @Route("/", name="applicant_oncefee_transaction")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FastSisdikBundle:CalonTransaksiPembayaranSekali')->findAll();

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/{id}/show", name="applicant_oncefee_transaction_show")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:CalonTransaksiPembayaranSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CalonTransaksiPembayaranSekali entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/new", name="applicant_oncefee_transaction_new")
     * @Template()
     */
    public function newAction()
    {
        $entity = new CalonTransaksiPembayaranSekali();
        $form   = $this->createForm(new CalonTransaksiPembayaranSekaliType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a new CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/create", name="applicant_oncefee_transaction_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:CalonTransaksiPembayaranSekali:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new CalonTransaksiPembayaranSekali();
        $form = $this->createForm(new CalonTransaksiPembayaranSekaliType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('applicant_oncefee_transaction_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/{id}/edit", name="applicant_oncefee_transaction_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:CalonTransaksiPembayaranSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CalonTransaksiPembayaranSekali entity.');
        }

        $editForm = $this->createForm(new CalonTransaksiPembayaranSekaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/{id}/update", name="applicant_oncefee_transaction_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:CalonTransaksiPembayaranSekali:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:CalonTransaksiPembayaranSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find CalonTransaksiPembayaranSekali entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CalonTransaksiPembayaranSekaliType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('applicant_oncefee_transaction_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a CalonTransaksiPembayaranSekali entity.
     *
     * @Route("/{id}/delete", name="applicant_oncefee_transaction_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:CalonTransaksiPembayaranSekali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find CalonTransaksiPembayaranSekali entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('applicant_oncefee_transaction'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
