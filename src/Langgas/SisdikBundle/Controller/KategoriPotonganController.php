<?php

namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\KategoriPotongan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/kategori-potongan")
 */
class KategoriPotonganController extends Controller
{
    /**
     * @Route("/", name="kategori-potongan")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('kategoriPotongan')
            ->from('LanggasSisdikBundle:KategoriPotongan', 'kategoriPotongan')
            ->where('kategoriPotongan.sekolah = :sekolah')
            ->orderBy('kategoriPotongan.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/", name="kategori-potongan_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:KategoriPotongan:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new KategoriPotongan();

        $form = $this->createCreateForm($entity);

        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.kategori.potongan.tersimpan', [
                    '%kategori%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('kategori-potongan_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param  KategoriPotongan             $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm(KategoriPotongan $entity)
    {
        $form = $this->createForm('sisdik_kategoripotongan', $entity, [
            'action' => $this->generateUrl('kategori-potongan_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="kategori-potongan_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new KategoriPotongan();
        $form = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="kategori-potongan_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:KategoriPotongan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity KategoriPotongan tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="kategori-potongan_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:KategoriPotongan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity KategoriPotongan tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
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
     * @param  KategoriPotongan             $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(KategoriPotongan $entity)
    {
        $form = $this->createForm('sisdik_kategoripotongan', $entity, [
            'action' => $this->generateUrl('kategori-potongan_update', [
                'id' => $entity->getId(),
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="kategori-potongan_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:KategoriPotongan:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:KategoriPotongan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity KategoriPotongan tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.kategori.potongan.terbarui', [
                    '%kategori%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('kategori-potongan_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="kategori-potongan_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:KategoriPotongan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity KategoriPotongan tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('kategori-potongan'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('kategori-potongan_delete', [
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.kategori.potongan', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
