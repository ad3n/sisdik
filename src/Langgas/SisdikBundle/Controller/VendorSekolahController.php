<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/vendor-sekolah")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class VendorSekolahController extends Controller
{
    /**
     * @Route("/", name="vendor_sekolah")
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
            ->select('vendorSekolah')
            ->from('LanggasSisdikBundle:VendorSekolah', 'vendorSekolah')
            ->leftJoin('vendorSekolah.sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder->where('vendorSekolah.sekolah = :sekolah');
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
     * @Route("/", name="vendor_sekolah_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:VendorSekolah:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new VendorSekolah();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.vendor.sekolah.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.vendor.sekolah');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('vendor_sekolah_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param  VendorSekolah                $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm(VendorSekolah $entity)
    {
        $form = $this->createForm('sisdik_vendorsekolah', $entity, [
            'action' => $this->generateUrl('vendor_sekolah_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="vendor_sekolah_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new VendorSekolah();
        $form   = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="vendor_sekolah_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity VendorSekolah tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="vendor_sekolah_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity penyedia jasa sms.');
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
     * @param  VendorSekolah                $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(VendorSekolah $entity)
    {
        $form = $this->createForm('sisdik_vendorsekolah', $entity, [
            'action' => $this->generateUrl('vendor_sekolah_update', [
                'id' => $entity->getId(),
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="vendor_sekolah_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:VendorSekolah:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity penyedia jasa sms.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.vendor.sekolah.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.vendor.sekolah');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('vendor_sekolah_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="vendor_sekolah_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity VendorSekolah tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('vendor_sekolah'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('vendor_sekolah_delete', [
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
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.vendor.sekolah', [], 'navigations')]->setCurrent(true);
    }
}
