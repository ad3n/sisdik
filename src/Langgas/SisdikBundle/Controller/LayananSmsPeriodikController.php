<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\LayananSmsPeriodik;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/layanan-sms-periodik")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN')")
 */
class LayananSmsPeriodikController extends Controller
{
    /**
     * @Route("/", name="layanan_smsperiodik")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('layananSmsPeriodik')
            ->from('LanggasSisdikBundle:LayananSmsPeriodik', 'layananSmsPeriodik')
            ->leftJoin('layananSmsPeriodik.tingkat', 'tingkat')
            ->where('layananSmsPeriodik.sekolah = :sekolah')
            ->orderBy('tingkat.urutan', 'ASC')
            ->addOrderBy('layananSmsPeriodik.jenisLayanan', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'daftarJenisLayanan' => array_merge(
                PilihanLayananSms::getDaftarLayananPeriodik()
            ),
            'daftarPerulangan' => LayananSmsPeriodik::getDaftarPerulangan(),
            'daftarBulan' => LayananSmsPeriodik::getDaftarNamaBulan(),
            'daftarHari' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/new", name="layanan_smsperiodik_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new LayananSmsPeriodik();
        $form = $this->createForm('sisdik_layanansmsperiodik', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="layanan_smsperiodik_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:LayananSmsPeriodik:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $sekolah = $this->getSekolah();

        $this->setCurrentMenu();

        $entity = new LayananSmsPeriodik();
        $form = $this->createForm('sisdik_layanansmsperiodik', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $layanan = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => $entity->getJenisLayanan(),
                        'tingkat' => $entity->getTingkat(),
                    ])
                ;

                if ($layanan instanceof LayananSmsPeriodik) {
                    throw new DBALException();
                }

                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.layanan.sms.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanan.sms.periodik');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('layanan_smsperiodik_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="layanan_smsperiodik_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity LayananSmsPeriodik tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarJenisLayanan' => array_merge(
                PilihanLayananSms::getDaftarLayananPeriodik()
            ),
            'daftarPerulangan' => LayananSmsPeriodik::getDaftarPerulangan(),
            'daftarBulan' => LayananSmsPeriodik::getDaftarNamaBulan(),
            'daftarHari' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="layanan_smsperiodik_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity LayananSmsPeriodik tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_layanansmsperiodik', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="layanan_smsperiodik_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:LayananSmsPeriodik:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $sekolah = $this->getSekolah();

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity LayananSmsPeriodik tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_layanansmsperiodik', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $layanan = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => $entity->getJenisLayanan(),
                        'tingkat' => $entity->getTingkat(),
                    ])
                ;

                if ($layanan instanceof LayananSmsPeriodik && $layanan->getId() != $entity->getId()) {
                    throw new DBALException();
                }

                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.layanan.sms.terbarui'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unik.layanan.sms');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('layanan_smsperiodik_edit', [
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
     * @Route("/{id}/delete", name="layanan_smsperiodik_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:LayananSmsPeriodik')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity LayananSmsPeriodik tak ditemukan.');
            }

            if ($this->get('security.authorization_checker')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.layanan.sms.terhapus'))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.layanan.sms.gagal.dihapus'))
            ;
        }

        return $this->redirect($this->generateUrl('layanan_smsperiodik'));
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
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.layanan.sms.periodik', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
