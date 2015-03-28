<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\JenisDokumenSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/jenis-dokumen-siswa")
 */
class JenisDokumenSiswaController extends Controller
{
    /**
     * @Route("/", name="jenisdokumensiswa")
     * @PreAuthorize("hasRole('ROLE_ADMIN')")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caritahun');

        $querybuilder = $em->createQueryBuilder()
            ->select('jenisDokumenSiswa')
            ->from('LanggasSisdikBundle:JenisDokumenSiswa', 'jenisDokumenSiswa')
            ->leftJoin('jenisDokumenSiswa.tahun', 'tahun')
            ->where('jenisDokumenSiswa.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('jenisDokumenSiswa.urutan', 'ASC')
            ->addOrderBy('jenisDokumenSiswa.namaDokumen', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('jenisDokumenSiswa.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']);
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
     * @Route("/", name="jenisdokumensiswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JenisDokumenSiswa:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new JenisDokumenSiswa;
        $form = $this->createForm('sisdik_jenisdokumensiswa', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.jenis.dokumen.siswa.tersimpan', [
                    '%nama%' => $entity->getNamaDokumen(),
                ]))
            ;

            return $this->redirect($this->generateUrl('jenisdokumensiswa_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/new", name="jenisdokumensiswa_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new JenisDokumenSiswa;
        $form = $this->createForm('sisdik_jenisdokumensiswa', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="jenisdokumensiswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
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
     * @Route("/{id}/edit", name="jenisdokumensiswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_jenisdokumensiswa', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="jenisdokumensiswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JenisDokumenSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_jenisdokumensiswa', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.jenis.dokumen.siswa.terbarui', [
                    '%nama%' => $entity->getNamaDokumen(),
                ]))
            ;

            return $this->redirect($this->generateUrl('jenisdokumensiswa_edit', [
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
     * @Route("/{id}/delete", name="jenisdokumensiswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JenisDokumenSiswa tak ditemukan.');
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
                    ->add('success', $this->get('translator')->trans('flash.jenis.dokumen.siswa.terhapus', [
                        '%nama%' => $entity->getNamaDokumen(),
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
                ->add('error', $this->get('translator')->trans('flash.jenis.dokumen.siswa.gagal.dihapus', [
                    '%nama%' => $entity->getNamaDokumen(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('jenisdokumensiswa'));
    }

    /**
     * @param integer $id
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
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.jenisdokumensiswa', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
