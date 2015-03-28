<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Tingkat;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/tingkat-kelas")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class TingkatController extends Controller
{
    /**
     * @Route("/", name="tingkat-kelas")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('tingkat')
            ->from('LanggasSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->orderBy('tingkat.kode', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/{id}/show", name="tingkat-kelas_show")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
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
     * @Route("/new", name="tingkat-kelas_new")
     * @Template()
     */
    public function newAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new Tingkat();

        $qbe = $em->createQueryBuilder();
        $queryUrutan = $em->createQueryBuilder()
            ->select($qbe->expr()->max('tingkat.urutan'))
            ->from('LanggasSisdikBundle:Tingkat', 'tingkat')
            ->where('tingkat.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
        ;
        $nomorUrut = $queryUrutan->getQuery()->getSingleScalarResult();
        $nomorUrut = $nomorUrut === null ? 0 : $nomorUrut;
        $nomorUrut++;

        $entity->setUrutan($nomorUrut);

        $form = $this->createForm('sisdik_tingkat', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="tingkat-kelas_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Tingkat:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new Tingkat();
        $form = $this->createForm('sisdik_tingkat', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.tingkat.kelas.tersimpan', [
                    '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('tingkat-kelas_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="tingkat-kelas_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_tingkat', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="tingkat-kelas_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Tingkat:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Tingkat')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_tingkat', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.tingkat.kelas.terbarui', [
                    '%nama%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('tingkat-kelas_edit', [
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
     * @Route("/{id}/delete", name="tingkat-kelas_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:Tingkat')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Tingkat tak ditemukan.');
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
                    ->add('success', $this->get('translator')->trans('flash.tingkat.kelas.terhapus', [
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
                ->add('error', $this->get('translator')->trans('flash.tingkat.kelas.gagal.dihapus'))
            ;
        }

        return $this->redirect($this->generateUrl('tingkat-kelas', [
            'page' => $this->getRequest()->get('page'),
        ]));
    }

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
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.tingkat', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
