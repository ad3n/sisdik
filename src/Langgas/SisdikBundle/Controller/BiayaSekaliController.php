<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\BiayaSekali;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Tahun;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/biaya-sekali")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_USER')")
 */
class BiayaSekaliController extends Controller
{
    /**
     * @Route("/", name="fee_once")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function indexAction()
    {
        $this->get('session')->remove('biayasekali_confirm');

        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_caribiayasekali');

        $querybuilder = $em->createQueryBuilder()
            ->select('biayaSekali')
            ->from('LanggasSisdikBundle:BiayaSekali', 'biayaSekali')
            ->leftJoin('biayaSekali.tahun', 'tahun')
            ->leftJoin('biayaSekali.jenisbiaya', 'jenisbiaya')
            ->where('tahun.sekolah = :sekolah')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('biayaSekali.urutan', 'ASC')
            ->addOrderBy('jenisbiaya.nama', 'ASC')
            ->setParameter('sekolah', $sekolah)
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('biayaSekali.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
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
     * @Route("/{id}/show", name="fee_once_show")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/new", name="fee_once_new")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new BiayaSekali();
        $form = $this->createForm('sisdik_biayasekali', $entity, [
            'mode' => 'new',
            'nominal' => null,
        ]);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="fee_once_create")
     * @Method("post")
     * @Template("LanggasSisdikBundle:BiayaSekali:new.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new BiayaSekali();
        $form = $this->createForm('sisdik_biayasekali', $entity, [
            'mode' => 'new',
            'nominal' => null,
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $qbsisabiaya = $em->createQueryBuilder()
                ->update('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.pembayaranSekali', 'pembayaran')
                ->leftJoin('pembayaran.daftarBiayaSekali', 'daftar')
                ->set('siswa.sisaBiayaSekali', 'siswa.sisaBiayaSekali + ' . $entity->getNominal())
                ->where('siswa.tahun = :tahun')
                ->andWhere('siswa.sisaBiayaSekali >= 0')
                ->setParameter('tahun', $entity->getTahun())
            ;

            try {
                $biayaSekali = $em->getRepository('LanggasSisdikBundle:BiayaSekali')
                    ->findOneBy([
                        'tahun' => $entity->getTahun(),
                        'jenisbiaya' => $entity->getJenisbiaya(),
                        'penjurusan' => $entity->getPenjurusan(),
                    ])
                ;

                if ($biayaSekali instanceof BiayaSekali) {
                    throw new DBALException();
                }

                $em->persist($entity);
                $em->flush();

                $qbsisabiaya->getQuery()->execute();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.once.inserted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_once_show', [
                'id' => $entity->getId(),
            ]));

        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/confirm", name="fee_once_edit_confirm")
     * @Template("LanggasSisdikBundle:BiayaSekali:edit.confirm.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editConfirmAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);
        if (!$entity && !($entity instanceof BiayaSekali)) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $form = $this->createForm('sisdik_confirm', null, [
            'sessiondata' => uniqid(),
        ]);

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {

                $sessiondata = $form['sessiondata']->getData();
                $this->get('session')->set('biayasekali_confirm', $sessiondata);

                return $this->redirect($this->generateUrl('fee_once_edit', [
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
     * @Route("/{id}/edit/{sessiondata}", name="fee_once_edit")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editAction($id, $sessiondata)
    {
        if ($this->get('session')->get('biayasekali_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_once_edit_confirm', [
                'id' => $id
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_biayasekali', $entity, [
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
     * @Route("/{id}/update/{sessiondata}", name="fee_once_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:BiayaSekali:edit.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function updateAction(Request $request, $id, $sessiondata)
    {
        if ($this->get('session')->get('biayasekali_confirm') != $sessiondata) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.konfirmasi.edit.gagal'))
            ;

            return $this->redirect($this->generateUrl('fee_once_edit_confirm', [
                'id' => $id,
            ]));
        }

        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_biayasekali', $entity, [
            'mode' => 'edit',
            'nominal' => $entity->getNominal(),
        ]);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            // penentuan ketika edit, tahun tidak bisa diubah hanya nominal yang bisa berubah
            // nominal bertambah
            // siswa sudah menggunakan -> sisa biaya tetap
            // siswa belum menggunakan -> sisa biaya ditambah
            // nominal berkurang
            // siswa sudah menggunakan -> sisa biaya tetap
            // siswa belum menggunakan -> sisa biaya dikurang
            $qbsiswa = $em->createQueryBuilder()
                ->select('DISTINCT(siswa.id)')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.pembayaranSekali', 'pembayaran')
                ->leftJoin('pembayaran.daftarBiayaSekali', 'daftar')
                ->where('siswa.tahun = :tahun')
                ->andWhere('daftar.biayaSekali = :biaya')
                ->setParameter('tahun', $entity->getTahun())
                ->setParameter('biaya', $entity)
            ;
            $result = $qbsiswa->getQuery()->getScalarResult();
            $siswaPemakaiBiaya = array_map('current', $result);

            if (is_array($siswaPemakaiBiaya) && count($siswaPemakaiBiaya) > 0) {
                $qbsisabiaya = $em->createQueryBuilder()
                    ->update('LanggasSisdikBundle:Siswa', 'siswa')
                    ->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.sisaBiayaSekali >= 0')
                    ->andWhere('siswa.id NOT IN (:pemakai)')
                    ->setParameter('tahun', $entity->getTahun())
                    ->setParameter('pemakai', $siswaPemakaiBiaya)
                ;

                if ($entity->getNominalSebelumnya() > $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaSekali', 'siswa.sisaBiayaSekali + ' . $entity->getNominal());
                } elseif ($entity->getNominalSebelumnya() < $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaSekali', 'siswa.sisaBiayaSekali - ' . $entity->getNominal());
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
                    ->add('success', $this->get('translator')->trans('flash.fee.once.updated'))
                ;

                $this->get('session')->remove('biayasekali_confirm');
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.once');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_once_show', [
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
     * @Route("/{id}/delete", name="fee_once_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaSekali tak ditemukan.');
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.oncefee');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.fee.once.deleted'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }

        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.fee.once.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('fee_once'));
    }

    /**
     * Finds total payables one time fee info
     *
     * @Route("/totalinfo/{tahun}/{json}", name="fee_once_totalinfo", defaults={"json"=0})
     */
    public function getFeeInfoTotalAction($tahun, $json)
    {
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->findBy([
            'tahun' => $tahun,
        ]);

        $total = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaSekali) {
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
     * Finds total once payment remains fee info
     *
     * @Route("/remains/{tahun}/{usedfee}/{json}", name="fee_once_remains")
     */
    public function getFeeInfoRemainAction($tahun, $usedfee, $json = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $usedfee = preg_replace('/,$/', '', $usedfee);

        $querybuilder = $em->createQueryBuilder()
            ->select('biaya')
            ->from('LanggasSisdikBundle:BiayaSekali', 'biaya')
            ->where('biaya.tahun = :tahun')
            ->andWhere('biaya.id NOT IN (:usedfee)')
            ->setParameter("tahun", $tahun)
            ->setParameter("usedfee", preg_split('/,/', $usedfee))
        ;
        $entities = $querybuilder->getQuery()->getResult();

        $feeamount = 0;
        $counter = 1;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaSekali) {
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
     * @Route("/info/{id}/{type}", name="fee_once_info")
     */
    public function getFeeInfoAction($id, $type = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LanggasSisdikBundle:BiayaSekali')->find($id);

        if ($entity instanceof BiayaSekali) {
            if ($type == 1) {
                $info = $entity->getJenisbiaya()->getNama() . " (" . number_format($entity->getNominal(), 0, ',', '.') . ")";
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
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.fee.once', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
