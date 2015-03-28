<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/sekolah-asal")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB', 'ROLE_USER')")
 */
class SekolahAsalController extends Controller
{
    /**
     * @Route("/", name="sekolahasal")
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
            ->select('sekolahAsal')
            ->from('LanggasSisdikBundle:SekolahAsal', 'sekolahAsal')
            ->where('sekolahAsal.sekolah = :sekolah')
            ->orderBy('sekolahAsal.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere('sekolahAsal.nama LIKE ?1 OR sekolahAsal.kode LIKE ?2 OR sekolahAsal.penghubung LIKE ?3')
                    ->setParameter(1, "%{$searchdata['searchkey']}%")
                    ->setParameter(2, "%{$searchdata['searchkey']}%")
                    ->setParameter(3, "%{$searchdata['searchkey']}%")
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
     * @Route("/", name="sekolahasal_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SekolahAsal:new.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new SekolahAsal();
        $form = $this->createForm('sisdik_sekolahasal', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.sekolah.asal.tersimpan', [
                    '%nama%' => $entity->getNama(),
                    '%kode%' => $entity->getKode(),
                ]))
            ;

            return $this->redirect($this->generateUrl('sekolahasal_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/new", name="sekolahasal_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new SekolahAsal();
        $form = $this->createForm('sisdik_sekolahasal', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @param Request $request
     * @Route("/ambil", name="sekolahasal_ajax_ambilnama")
     * @Method("GET")
     */
    public function ajaxGetSekolahAsal(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');
        $id = $this->getRequest()->query->get('id');

        $querybuilder = $em->createQueryBuilder()
            ->select('sekolahAsal')
            ->from('LanggasSisdikBundle:SekolahAsal', 'sekolahAsal')
            ->where('sekolahAsal.sekolah = :sekolah')
            ->orderBy('sekolahAsal.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        if ($id != '') {
            $querybuilder
                ->andWhere('sekolahAsal.id = :id')
                ->setParameter('id', $id)
            ;
        } else {
            $querybuilder
                ->andWhere('sekolahAsal.nama LIKE ?1 OR sekolahAsal.kode LIKE ?2')
                ->setParameter(1, "%$filter%")
                ->setParameter(2, "%$filter%")
            ;
        }

        $results = $querybuilder->getQuery()->getResult();

        $retval = [];
        foreach ($results as $result) {
            if ($result instanceof SekolahAsal) {
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
     * @Route("/{id}", name="sekolahasal_show")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
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
     * @Route("/{id}/edit", name="sekolahasal_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_sekolahasal', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="sekolahasal_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SekolahAsal:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:SekolahAsal')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_sekolahasal', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.sekolah.asal.terbarui', [
                    '%nama%' => $entity->getNama(),
                    '%kode%' => $entity->getKode(),
                ]))
            ;

            return $this->redirect($this->generateUrl('sekolahasal_edit', [
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
     * @Route("/{id}/delete", name="sekolahasal_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN, ROLE_KEPALA_SEKOLAH, ROLE_WAKIL_KEPALA_SEKOLAH, ROLE_PANITIA_PSB")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:SekolahAsal')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity SekolahAsal tak ditemukan.');
            }

            if ($this->get('security.authorization_checker')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.sekolah.asal.terhapus', [
                        '%nama%' => $entity->getNama(),
                        '%kode%' => $entity->getKode(),
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
                ->add('error', $this->get('translator')->trans('flash.sekolah.asal.gagal.dihapus', [
                    '%nama%' => $entity->getNama(),
                    '%kode%' => $entity->getKode(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('sekolahasal'));
    }

    /**
     * @param mixed $id
     *
     * @return \Symfony\Component\Form\Form
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
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.sekolahasal', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
