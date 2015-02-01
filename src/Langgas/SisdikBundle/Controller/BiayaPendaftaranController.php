<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Tahun;
use Langgas\SisdikBundle\Entity\Gelombang;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/biaya-pendaftaran")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_USER')")
 */
class BiayaPendaftaranController extends Controller
{
    /**
     * @Route("/", name="fee_registration")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function indexAction()
    {
        $this->get('session')->remove('biaya_confirm');

        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caribiaya');

        $querybuilder = $em->createQueryBuilder()
            ->select('biayaPendaftaran')
            ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biayaPendaftaran')
            ->leftJoin('biayaPendaftaran.tahun', 'tahun')
            ->leftJoin('biayaPendaftaran.gelombang', 'gelombang')
            ->leftJoin('biayaPendaftaran.jenisbiaya', 'jenisbiaya')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'ASC')
            ->addOrderBy('biayaPendaftaran.urutan', 'ASC')
            ->addOrderBy('jenisbiaya.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('biayaPendaftaran.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;
            }
            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('biayaPendaftaran.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder
                    ->andWhere("(jenisbiaya.nama LIKE :jenisbiaya OR jenisbiaya.kode = :kodejenisbiaya)")
                    ->setParameter('jenisbiaya', "%{$searchdata['jenisbiaya']}%")
                    ->setParameter('kodejenisbiaya', $searchdata['jenisbiaya'])
                ;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 20);

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/{id}/show", name="fee_registration_show")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

        if (! $entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="fee_registration_new")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm('sisdik_biayapendaftaran', $entity, [
            'mode' => 'new',
            'nominal' => null,
        ]);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="fee_registration_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:new.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm('sisdik_biayapendaftaran', $entity, [
            'mode' => 'new',
            'nominal' => null,
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $qbsisabiaya = $em->createQueryBuilder()
                ->update('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                ->leftJoin('pembayaran.daftarBiayaPendaftaran', 'daftar')
                ->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran + '.$entity->getNominal())
                ->where('siswa.tahun = :tahun')
                ->andWhere('siswa.gelombang = :gelombang')
                ->andWhere('siswa.sisaBiayaPendaftaran >= 0')
                ->setParameter('tahun', $entity->getTahun())
                ->setParameter('gelombang', $entity->getGelombang())
            ;

            try {
                $biayaPendaftaran = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')
                    ->findOneBy([
                        'tahun' => $entity->getTahun(),
                        'gelombang' => $entity->getGelombang(),
                        'penjurusan' => $entity->getPenjurusan(),
                        'jenisbiaya' => $entity->getJenisbiaya(),
                    ])
                ;

                if ($biayaPendaftaran instanceof BiayaPendaftaran) {
                    throw new DBALException();
                }

                $em->persist($entity);
                $em->flush();

                $qbsisabiaya->getQuery()->execute();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.registration.inserted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_registration_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/confirm", name="fee_registration_edit_confirm")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:edit.confirm.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editConfirmAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (!$entity && !($entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $form = $this->createForm('sisdik_confirm', null, [
            'sessiondata' => uniqid(),
        ]);

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {
                $sessiondata = $form['sessiondata']->getData();
                $this->get('session')->set('biaya_confirm', $sessiondata);

                return $this->redirect($this->generateUrl('fee_registration_edit', [
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
     * @Route("/{id}/edit/{sessiondata}", name="fee_registration_edit")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editAction($id, $sessiondata)
    {
        if ($this->get('session')->get('biaya_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_registration_edit_confirm', [
                'id' => $id
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (! $entity && ! ($entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_biayapendaftaran', $entity, [
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
     * @Route("/{id}/update/{sessiondata}", name="fee_registration_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:edit.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function updateAction(Request $request, $id, $sessiondata)
    {
        if ($this->get('session')->get('biaya_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_registration_edit_confirm', [
                'id' => $id,
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (! (is_object($entity) && $entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_biayapendaftaran', $entity, [
            'mode' => 'edit',
            'nominal' => $entity->getNominal(),
        ]);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            // penentuan ketika edit, tahun dan gelombang tidak bisa diubah hanya nominal yang bisa berubah
            // nominal bertambah
            // siswa sudah menggunakan -> sisa biaya tetap
            // siswa belum menggunakan -> sisa biaya ditambah
            // nominal berkurang
            // siswa sudah menggunakan -> sisa biaya tetap
            // siswa belum menggunakan -> sisa biaya dikurang
            $qbsiswa = $em->createQueryBuilder()
                ->select('DISTINCT(siswa.id)')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                ->leftJoin('pembayaran.daftarBiayaPendaftaran', 'daftar')
                ->where('siswa.tahun = :tahun')
                ->andWhere('siswa.gelombang = :gelombang')
                ->andWhere('daftar.biayaPendaftaran = :biaya')
                ->setParameter('tahun', $entity->getTahun())
                ->setParameter('gelombang', $entity->getGelombang())
                ->setParameter('biaya', $entity)
            ;
            $result = $qbsiswa->getQuery()->getScalarResult();
            $siswaPemakaiBiaya = array_map('current', $result);

            if (is_array($siswaPemakaiBiaya) && count($siswaPemakaiBiaya) > 0) {
                $qbsisabiaya = $em->createQueryBuilder()
                    ->update('LanggasSisdikBundle:Siswa', 'siswa')
                    ->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->andWhere('siswa.sisaBiayaPendaftaran >= 0')
                    ->andWhere('siswa.id NOT IN (:pemakai)')
                    ->setParameter('tahun', $entity->getTahun())
                    ->setParameter('gelombang', $entity->getGelombang())
                    ->setParameter('pemakai', $siswaPemakaiBiaya)
                ;

                if ($entity->getNominalSebelumnya() > $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran + '.$entity->getNominal());
                } elseif ($entity->getNominalSebelumnya() < $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran - '.$entity->getNominal());
                }
            }

            try {
                $em->persist($entity);
                $em->flush();

                if (is_array($siswaPemakaiBiaya) && count($siswaPemakaiBiaya) > 0) {
                    $qbsisabiaya->getQuery()->execute();
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.registration.updated'))
                ;

                $this->get('session')->remove('biaya_confirm');
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message.$e);
            }

            return $this->redirect($this->generateUrl('fee_registration_show', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'sessiondata' => $sessiondata,
        ];
    }

    /**
     * @Route("/{id}/delete", name="fee_registration_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.registrationfee');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.registration.deleted'))
                ;
            } catch (\Exception $e) {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('info', $this->get('translator')->trans('exception.delete.restrict.registrationfee'))
                ;

                return $this->redirect($this->generateUrl('fee_registration_show', [
                    'id' => $id,
                ]));
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.fee.registration.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('fee_registration'));
    }

    /**
     * Mencari info total biaya pendaftaran yang bisa dibayar
     *
     * @Route("/totalinfo/{tahun}/{gelombang}/{penjurusan}/{json}", name="fee_registration_totalinfo")
     */
    public function getFeeInfoTotalAction($tahun, $gelombang, $penjurusan = -999, $json = 0)
    {
        $em = $this->getDoctrine()->getManager();

        if ($penjurusan === -999) {
            $entities = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL')
                ->setParameter('tahun', $tahun)
                ->setParameter('gelombang', $gelombang)
                ->getQuery()
                ->getResult()
            ;
        } else {
            $entities = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                ->setParameter('tahun', $tahun)
                ->setParameter('gelombang', $gelombang)
                ->setParameter('penjurusan', $penjurusan)
                ->getQuery()
                ->getResult()
            ;
        }

        $total = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $total += $entity->getNominal();
            }
        }

        if ($json == 1) {
            $string = json_encode([
                "biaya" => $total,
            ]);

            return new Response($string, 200, [
                'Content-Type' => 'application/json',
            ]);
        } else {
            return new Response(number_format($total, 0, ',', '.'));
        }
    }

    /**
     * Mencari info jumlah sisa biaya pendaftaran
     *
     * @Route("/remains/{tahun}/{gelombang}/{usedfee}/{penjurusan}/{json}", name="fee_registration_remains")
     */
    public function getFeeInfoRemainAction($tahun, $gelombang, $usedfee, $penjurusan = -999, $json = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $usedfee = preg_replace('/,$/', '', $usedfee);

        if ($penjurusan === -999) {
            $entities = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL')
                ->andWhere('biaya.id NOT IN (:usedfee)')
                ->setParameter('tahun', $tahun)
                ->setParameter('gelombang', $gelombang)
                ->setParameter("usedfee", preg_split('/,/', $usedfee))
                ->getQuery()
                ->getResult()
            ;
        } else {
            $entities = $em->createQueryBuilder()
                ->select('biaya')
                ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
                ->where('biaya.tahun = :tahun')
                ->andWhere('biaya.gelombang = :gelombang')
                ->andWhere('biaya.penjurusan IS NULL OR biaya.penjurusan = :penjurusan')
                ->andWhere('biaya.id NOT IN (:usedfee)')
                ->setParameter('tahun', $tahun)
                ->setParameter('gelombang', $gelombang)
                ->setParameter('penjurusan', $penjurusan)
                ->setParameter("usedfee", preg_split('/,/', $usedfee))
                ->getQuery()
                ->getResult()
            ;
        }

        $feeamount = 0;
        $counter = 1;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $feeamount += $entity->getNominal();
            }
        }

        if ($json == 1) {
            $string = json_encode([
                "biaya" => $feeamount,
            ]);

            return new Response($string, 200, [
                'Content-Type' => 'application/json',
            ]);
        } else {
            return new Response(number_format($feeamount, 0, ',', '.'));
        }
    }

    /**
     * Finds info of a fee
     *
     * @Route("/info/{id}/{type}", name="fee_registration_info")
     */
    public function getFeeInfoAction($id, $type = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

        if ($entity instanceof BiayaPendaftaran) {
            if ($type == 1) {
                $info = $entity->getJenisbiaya()->getNama()." (".number_format($entity->getNominal(), 0, ',', '.').")";
            } elseif ($type == 2) {
                $info = $entity->getJenisbiaya()->getNama();
            } elseif ($type == 3) {
                $info = number_format($entity->getNominal(), 0, ',', '.');
            }
        } else {
            $info = $this->get('translator')->trans('label.fee.undefined');
        }

        return new Response($info);
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.fee.registration', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
