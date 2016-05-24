<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pilihan-layanan-sms")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class PilihanLayananSmsController extends Controller
{
    /**
     * @Route("/", name="layanansms")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();
        $em = $this->getDoctrine()->getManager();
        $searchform = $this->createForm('sisdik_carilayanansms');

        $querybuilder = $em->createQueryBuilder()
            ->select('sekolah')
            ->from('LanggasSisdikBundle:Sekolah', 'sekolah')
            ->orderBy('sekolah.nama', 'ASC')
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['sekolah'] != '') {
                $querybuilder
                    ->where('sekolah.id = :sekolah')
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
     * @Route("/{id}", name="layanansms_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();
        $em = $this->getDoctrine()->getManager();
        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findBy([
                'sekolah' => $id,
                'status' => true,
            ])
        ;

        $sekolah = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($id);

        return [
            'sekolah' => $sekolah,
            'entity' => $entity,
            'daftarJenisLayanan' => $daftarLayanan,
        ];
    }

    /**
     * @Route("/{id}/edit", name="layanansms_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
            ->findBy([
                'sekolah' => $sekolah,
            ])
        ;

        $editForm = $this->createForm('sisdik_pilihanlayanansms', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="layanansms_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanLayananSms:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_pilihanlayanansms', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.layanansms.terbarui', [
                        '%sekolah%' => $entity->getSekolah()->getNama(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanansms');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('layanansms_edit', [
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
     * @Route("/{id}/delete", name="layanansms_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity PilihanLayananSms tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.layanansms.terhapus', [
                    '%sekolah%' => $entity->getSekolah()->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.layanansms.gagal.dihapus', [
                    '%sekolah%' => $entity->getSekolah()->getNama(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('layanansms'));
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

    /**
     * Mencari layanan sms yang aktif di sekolah tertentu
     *
     * @Route("layanan-aktif/{id}/", name="layanansms_aktif")
     */
    public function getInfoLayananSms($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->createQueryBuilder()
            ->select('pilihanLayananSms')
            ->from('LanggasSisdikBundle:PilihanLayananSms', 'pilihanLayananSms')
            ->where('pilihanLayananSms.sekolah = :sekolah')
            ->andWhere('pilihanLayananSms.status = :status')
            ->setParameter('sekolah', $id)
            ->setParameter('status', true)
            ->getQuery()
            ->getResult()
        ;

        $daftarLayanan = PilihanLayananSms::getDaftarLayananSMS();

        $layananAktif = [];
        foreach ($entities as $entity) {
            if ($entity instanceof PilihanLayananSms) {
                $layananAktif[] = $daftarLayanan[$entity->getJenisLayanan()];
            }
        }

        return new JsonResponse($layananAktif);
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pengaturan.sisdik', [], 'navigations')][$translator->trans('links.layanansms', [], 'navigations')]->setCurrent(true);
    }
}
