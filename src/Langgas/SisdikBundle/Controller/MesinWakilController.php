<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\MesinWakil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/mesin-wakil")
 */
class MesinWakilController extends Controller
{
    /**
     * @Route("/", name="mesin-wakil")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari_sekolah');

        $querybuilder = $em->createQueryBuilder()
            ->select('mesinWakil')
            ->from('LanggasSisdikBundle:MesinWakil', 'mesinWakil')
            ->leftJoin('mesinWakil.sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder->where('mesinWakil.sekolah = :sekolah');
                $querybuilder->setParameter("sekolah", $searchdata['sekolah']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/", name="mesin-wakil_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:MesinWakil:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new MesinWakil;
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('mesin-wakil_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param  MesinWakil                   $entity
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MesinWakil $entity)
    {
        $form = $this->createForm('sisdik_mesinwakil', $entity, [
            'action' => $this->generateUrl('mesin-wakil_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="mesin-wakil_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MesinWakil;
        $form = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="mesin-wakil_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinWakil')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity MesinWakil.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="mesin-wakil_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinWakil')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity MesinWakil.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @param MesinWakil $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(MesinWakil $entity)
    {
        $form = $this->createForm('sisdik_mesinwakil', $entity, [
            'action' => $this->generateUrl('mesin-wakil_update', [
                'id' => $entity->getId(),
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="mesin-wakil_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:MesinWakil:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:MesinWakil')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity MesinWakil.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('mesin-wakil_edit', ['id' => $id]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="mesin-wakil_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:MesinWakil')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Tak bisa menemukan entity MesinWakil.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('mesin-wakil'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mesin-wakil_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', 'submit', [
                'label' => 'label.delete',
                'attr' => [
                    'class' => 'btn alternative icon danger remove',
                ],
            ])
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.pengaturan.sisdik', [], 'navigations')][$this->get('translator')->trans('links.mesin.wakil', [], 'navigations')]->setCurrent(true);
    }
}
