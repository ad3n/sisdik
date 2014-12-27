<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/referensi")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB', 'ROLE_USER')")
 */
class ReferensiController extends Controller
{
    /**
     * @Route("/", name="referensi")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_cari');

        $querybuilder = $em->createQueryBuilder()
            ->select('referensi')
            ->from('LanggasSisdikBundle:Referensi', 'referensi')
            ->where('referensi.sekolah = :sekolah')
            ->orderBy('referensi.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('referensi.nama LIKE :nama');
                $querybuilder->setParameter('nama', "%{$searchdata['searchkey']}%");
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
     * @Route("/new", name="referensi_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new Referensi();
        $form = $this->createForm('sisdik_referensi', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * Creates a new Referensi entity.
     *
     * @Route("/", name="referensi_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Referensi:new.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new Referensi();
        $form = $this->createForm('sisdik_referensi', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.perujuk.tersimpan', [
                    '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('referensi_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param Request $request
     * @Route("/ambil", name="referensi_ajax_ambilnama")
     * @Method("GET")
     */
    public function ajaxGetReferensiAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()
            ->select('referensi')
            ->from('LanggasSisdikBundle:Referensi', 'referensi')
            ->where('referensi.sekolah = :sekolah')
            ->orderBy('referensi.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($id != '') {
            $querybuilder
                ->andWhere('referensi.id = :id')
                ->setParameter('id', $id)
            ;
        } else {
            $querybuilder
                ->andWhere('referensi.nama LIKE :filter')
                ->setParameter('filter', "%$filter%")
            ;
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            if ($result instanceof Referensi) {
                $retval[] = [
                    'id' => $result->getId(),
                    'label' =>/** @Ignore */ $result->getNama(),
                    'value' => $result->getNama(),
                ];
            }
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/{id}", name="referensi_show")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="referensi_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_referensi', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="referensi_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Referensi:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Referensi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_referensi', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.perujuk.terbarui', [
                    '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('referensi_edit', [
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
     * @Route("/{id}/delete", name="referensi_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Referensi')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Referensi tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.perujuk.terhapus', [
                        '%nama%' => $entity->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.perujuk.gagal.dihapus', [
                    '%nama%' => $entity->getNama(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('referensi'));
    }

    /**
     * @param mixed $id
     *
     * @return Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id,
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.referensi', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
