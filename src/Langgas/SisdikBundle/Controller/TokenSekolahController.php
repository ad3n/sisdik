<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\TokenSekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/token-sekolah")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class TokenSekolahController extends Controller
{
    /**
     * @Route("/", name="token-sekolah")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carisekolah');

        $querybuilder = $em->createQueryBuilder()
            ->select('tokenSekolah')
            ->from('LanggasSisdikBundle:TokenSekolah', 'tokenSekolah')
            ->leftJoin('tokenSekolah.sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] instanceof Sekolah) {
                $querybuilder
                    ->where('t.sekolah = :sekolah')
                    ->setParameter("sekolah", $searchdata['sekolah'])
                ;
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
     * @Route("/", name="token-sekolah_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:TokenSekolah:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new TokenSekolah();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity->setMesinProxy($entity->generateRandomToken());
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('token-sekolah_show', ['id' => $entity->getId()]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param TokenSekolah $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm(TokenSekolah $entity)
    {
        $form = $this->createForm('sisdik_tokensekolah', $entity, [
            'action' => $this->generateUrl('token-sekolah_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="token-sekolah_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new TokenSekolah();
        $form   = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="token-sekolah_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="token-sekolah_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
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
     * @param TokenSekolah $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(TokenSekolah $entity)
    {
        $form = $this->createForm('sisdik_tokensekolah', $entity, [
            'action' => $this->generateUrl('token-sekolah_update', [
                'id' => $entity->getId()
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="token-sekolah_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:TokenSekolah:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TokenSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return
                $this->redirect($this->generateUrl('token-sekolah_edit', [
                    'id' => $id,
                ])
            );
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="token-sekolah_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:TokenSekolah')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity TokenSekolah tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('token-sekolah'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('token-sekolah_delete', [
                'id' => $id,
            ]))
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
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.token.sekolah', [], 'navigations')]->setCurrent(true);
    }
}
