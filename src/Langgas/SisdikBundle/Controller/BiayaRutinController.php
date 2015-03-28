<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaRutin;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\Jenisbiaya;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Tahun;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/biaya-berulang")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_USER')")
 */
class BiayaRutinController extends Controller
{
    /**
     * @Route("/", name="fee_recur")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function indexAction()
    {
        $this->get('session')->remove('biayarutin_confirm');

        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caribiaya_sr');

        $querybuilder = $em->createQueryBuilder()
            ->select('biayaRutin')
            ->from('LanggasSisdikBundle:BiayaRutin', 'biayaRutin')
            ->leftJoin('biayaRutin.tahun', 'tahun')
            ->leftJoin('biayaRutin.penjurusan', 'penjurusan')
            ->leftJoin('biayaRutin.jenisbiaya', 'jenisbiaya')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('penjurusan.nama', 'ASC')
            ->addOrderBy('biayaRutin.urutan', 'ASC')
            ->addOrderBy('jenisbiaya.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('biayaRutin.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;
            }

            if ($searchdata['jenisbiaya'] instanceof Jenisbiaya) {
                $querybuilder
                    ->andWhere("(jenisbiaya.nama LIKE :namajenisbiaya OR jenisbiaya.kode = :kodejenisbiaya)")
                    ->setParameter('namajenisbiaya', "%{$searchdata['jenisbiaya']}%")
                    ->setParameter('kodejenisbiaya', $searchdata['jenisbiaya'])
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'daftarPerulangan' => BiayaRutin::getDaftarPerulangan(),
            'daftarBulan' => BiayaRutin::getDaftarNamaBulan(),
            'daftarHari' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/{id}/show", name="fee_recur_show")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarPerulangan' => BiayaRutin::getDaftarPerulangan(),
            'daftarBulan' => BiayaRutin::getDaftarNamaBulan(),
            'daftarHari' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/new", name="fee_recur_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new BiayaRutin();
        $form = $this->createForm('sisdik_biayarutin', $entity, [
            'mode' => 'new',
            'nominal' => null,
        ]);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="fee_recur_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:BiayaRutin:new.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function createAction()
    {
        $this->setCurrentMenu();

        $entity = new BiayaRutin();
        $request = $this->getRequest();
        $form = $this->createForm('sisdik_biayarutin', $entity);
        $form->submit($request);

        switch ($form->get('perulangan')->getData()) {
            case 'b-mingguan':
                if (!$form->get('mingguanHariKe')->getData()) {
                    $message = $this->get('translator')->trans('exception.perulangan.mingguan.hari');
                    $form->get('mingguanHariKe')->addError(new FormError($message));
                }

                break;
            case 'c-bulanan':
                if (!$form->get('bulananHariKe')->getData()) {
                    $message = $this->get('translator')->trans('exception.perulangan.bulanan.lb.tanggal');
                    $form->get('bulananHariKe')->addError(new FormError($message));
                }

                break;
            case 'd-triwulan':
            case 'e-caturwulan':
            case 'f-semester':
            case 'g-tahunan':
                if (!$form->get('bulananHariKe')->getData()) {
                    $message = $this->get('translator')->trans('exception.perulangan.bulanan.lb.tanggal');
                    $form->get('bulananHariKe')->addError(new FormError($message));
                }

                if (!$form->get('bulanAwal')->getData()) {
                    $message = $this->get('translator')->trans('exception.perulangan.triwulan.lb.bulan');
                    $form->get('bulanAwal')->addError(new FormError($message));
                }

                break;
        }

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $biayaRutin = $em->getRepository('LanggasSisdikBundle:BiayaRutin')
                    ->findOneBy([
                        'tahun' => $entity->getTahun(),
                        'penjurusan' => $entity->getPenjurusan(),
                        'jenisbiaya' => $entity->getJenisbiaya(),
                        'perulangan' => $entity->getPerulangan(),
                    ])
                ;

                if ($biayaRutin instanceof BiayaRutin) {
                    throw new DBALException();
                }

                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.recur.inserted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.recur');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_recur_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/confirm", name="fee_recur_edit_confirm")
     * @Template("LanggasSisdikBundle:BiayaRutin:edit.confirm.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editConfirmAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($id);
        if (!$entity && !($entity instanceof BiayaRutin)) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $form = $this->createForm('sisdik_confirm', null, [
            'sessiondata' => uniqid(),
        ]);

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {
                $sessiondata = $form['sessiondata']->getData();
                $this->get('session')->set('biayarutin_confirm', $sessiondata);

                return $this->redirect($this->generateUrl('fee_recur_edit', [
                    'id' => $entity->getId(),
                    'sessiondata' => $sessiondata,
                ]));
            } else {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
                ;
            }
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit/{sessiondata}", name="fee_recur_edit")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editAction($id, $sessiondata)
    {
        if ($this->get('session')->get('biayarutin_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_recur_edit_confirm', [
                'id' => $id
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_biayarutin', $entity, [
            'mode' => 'edit',
            'nominal' => $entity->getNominal(),
        ]);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'sessiondata' => $sessiondata,
        ];
    }

    /**
     * @Route("/{id}/update/{sessiondata}", name="fee_recur_update")
     * @Method("post")
     * @Template("LanggasSisdikBundle:BiayaRutin:edit.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function updateAction($id, $sessiondata)
    {
        if ($this->get('session')->get('biayarutin_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_recur_edit_confirm', [
                'id' => $id,
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_biayarutin', $entity, [
            'mode' => 'edit',
            'nominal' => $entity->getNominal(),
        ]);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.recur.updated'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.recur');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_recur_show', [
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
     * @Route("/{id}/delete", name="fee_recur_delete")
     * @Method("post")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:BiayaRutin')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaRutin tak ditemukan.');
            }

            if ($this->get('security.authorization_checker')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.biaya.rutin');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.recur.deleted'))
                ;
            } catch (\Exception $e) {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('info', $this->get('translator')->trans('exception.delete.restrict.biaya.rutin'))
                ;

                return $this->redirect($this->generateUrl('fee_recur_show', [
                    'id' => $id,
                ]));
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.fee.recur.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('fee_recur'));
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.fee.recur', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
