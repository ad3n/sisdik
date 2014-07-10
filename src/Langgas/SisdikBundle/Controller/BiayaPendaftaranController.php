<?php
namespace Langgas\SisdikBundle\Controller;

use Symfony\Component\HttpFoundation\Session\Session;
use Langgas\SisdikBundle\Form\ConfirmationType;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\BiayaPendaftaran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\BiayaPendaftaranType;
use Langgas\SisdikBundle\Form\BiayaSearchFormType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * BiayaPendaftaran controller.
 *
 * @Route("/fee/registration")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_USER')")
 */
class BiayaPendaftaranController extends Controller
{

    /**
     * Lists all BiayaPendaftaran entities.
     *
     * @Route("/", name="fee_registration")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function indexAction()
    {
        $this->get('session')->remove('biaya_confirm');

        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()
            ->select('t')
            ->from('LanggasSisdikBundle:BiayaPendaftaran', 't')
            ->leftJoin('t.tahun', 't2')
            ->leftJoin('t.gelombang', 't3')
            ->leftJoin('t.jenisbiaya', 't4')
            ->where('t2.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah->getId())
            ->orderBy('t2.tahun', 'DESC')
            ->addOrderBy('t3.urutan', 'ASC')
            ->addOrderBy('t.urutan', 'ASC')
            ->addOrderBy('t4.nama', 'ASC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t.tahun = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }
            if ($searchdata['gelombang'] != '') {
                $querybuilder->andWhere('t.gelombang = :gelombang');
                $querybuilder->setParameter('gelombang', $searchdata['gelombang']->getId());
            }
            if ($searchdata['jenisbiaya'] != '') {
                $querybuilder->andWhere("(t4.nama LIKE :jenisbiaya OR t4.kode = :kodejenisbiaya)");
                $querybuilder->setParameter('jenisbiaya', "%{$searchdata['jenisbiaya']}%");
                $querybuilder->setParameter('kodejenisbiaya', $searchdata['jenisbiaya']);
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1), 20);

        return array(
            'pagination' => $pagination,
            'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a BiayaPendaftaran entity.
     *
     * @Route("/{id}/show", name="fee_registration_show")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function showAction($id)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

        if (! $entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView()
        );
    }

    /**
     * Displays a form to create a new BiayaPendaftaran entity.
     *
     * @Route("/new", name="fee_registration_new")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function newAction()
    {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container, 'new', null), $entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }

    /**
     * Creates a new BiayaPendaftaran entity.
     *
     * @Route("/create", name="fee_registration_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:new.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function createAction(Request $request)
    {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container, 'new', null), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $qbsisabiaya = $em->createQueryBuilder()
                ->update('LanggasSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.pembayaranPendaftaran', 'pembayaran')
                ->leftJoin('pembayaran.daftarBiayaPendaftaran', 'daftar')
                ->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran + ' . $entity->getNominal())
                ->where('siswa.tahun = :tahun')
                ->andWhere('siswa.gelombang = :gelombang')
                ->andWhere('siswa.sisaBiayaPendaftaran >= 0')
                ->setParameter('tahun', $entity->getTahun()
                ->getId())
                ->setParameter('gelombang', $entity->getGelombang()
                ->getId());

            try {
                $em->persist($entity);
                $em->flush();

                $qbsisabiaya->getQuery()->execute();

                $this->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')
                    ->trans('flash.fee.registration.inserted'));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('fee_registration_show', array(
                'id' => $entity->getId()
            )));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/confirm", name="fee_registration_edit_confirm")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:edit.confirm.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editConfirmAction($id)
    {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (! $entity && ! ($entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $form = $this->createForm(new ConfirmationType($this->container, uniqid()));

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {

                $sessiondata = $form['sessiondata']->getData();
                $this->get('session')->set('biaya_confirm', $sessiondata);

                return $this->redirect($this->generateUrl('fee_registration_edit', array(
                    'id' => $entity->getId(),
                    'sessiondata' => $sessiondata
                )));
            } else {
                $this->get('session')
                    ->getFlashBag()
                    ->add('error', $this->get('translator')
                    ->trans('flash.konfirmasi.edit.gagal'));
            }
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }

    /**
     * Displays a form to edit an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/edit/{sessiondata}", name="fee_registration_edit")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editAction($id, $sessiondata)
    {
        if ($this->get('session')->get('biaya_confirm') != $sessiondata) {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')
                ->trans('flash.konfirmasi.edit.gagal'));

            return $this->redirect($this->generateUrl('fee_registration_edit_confirm', array(
                'id' => $id
            )));
        }

        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (! $entity && ! ($entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $editForm = $this->createForm(new BiayaPendaftaranType($this->container, 'edit', $entity->getNominal()), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'sessiondata' => $sessiondata
        );
    }

    /**
     * Edits an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/update/{sessiondata}", name="fee_registration_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:BiayaPendaftaran:edit.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function updateAction(Request $request, $id, $sessiondata)
    {
        if ($this->get('session')->get('biaya_confirm') != $sessiondata) {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')
                ->trans('flash.konfirmasi.edit.gagal'));

            return $this->redirect($this->generateUrl('fee_registration_edit_confirm', array(
                'id' => $id
            )));
        }

        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);
        if (! (is_object($entity) && $entity instanceof BiayaPendaftaran)) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new BiayaPendaftaranType($this->container, 'edit', $entity->getNominal()), $entity);
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
                ->setParameter('tahun', $entity->getTahun()
                ->getId())
                ->setParameter('gelombang', $entity->getGelombang()
                ->getId())
                ->setParameter('biaya', $entity->getId());
            $result = $qbsiswa->getQuery()->getScalarResult();
            $siswaPemakaiBiaya = array_map('current', $result);

            if (is_array($siswaPemakaiBiaya) && count($siswaPemakaiBiaya) > 0) {
                $qbsisabiaya = $em->createQueryBuilder()
                    ->update('LanggasSisdikBundle:Siswa', 'siswa')
                    ->where('siswa.tahun = :tahun')
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->andWhere('siswa.sisaBiayaPendaftaran >= 0')
                    ->andWhere('siswa.id NOT IN (:pemakai)')
                    ->setParameter('tahun', $entity->getTahun()
                    ->getId())
                    ->setParameter('gelombang', $entity->getGelombang()
                    ->getId())
                    ->setParameter('pemakai', $siswaPemakaiBiaya);

                if ($entity->getNominalSebelumnya() > $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran + ' . $entity->getNominal());
                } elseif ($entity->getNominalSebelumnya() < $entity->getNominal()) {
                    $qbsisabiaya->set('siswa.sisaBiayaPendaftaran', 'siswa.sisaBiayaPendaftaran - ' . $entity->getNominal());
                }
            }

            try {

                $em->persist($entity);
                $em->flush();

                if (is_array($siswaPemakaiBiaya) && count($siswaPemakaiBiaya) > 0) {
                    $qbsisabiaya->getQuery()->execute();
                }

                $this->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')
                    ->trans('flash.fee.registration.updated'));

                $this->get('session')->remove('biaya_confirm');
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message . $e);
            }

            return $this->redirect($this->generateUrl('fee_registration_show', array(
                'id' => $id
            )));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'sessiondata' => $sessiondata
        );
    }

    /**
     * Deletes a BiayaPendaftaran entity.
     *
     * @Route("/{id}/delete", name="fee_registration_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function deleteAction(Request $request, $id)
    {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

            if (! $entity) {
                throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.registrationfee');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')
                    ->trans('flash.fee.registration.deleted'));
            } catch (\Exception $e) {
                $this->get('session')
                    ->getFlashBag()
                    ->add('info', $this->get('translator')
                    ->trans('exception.delete.restrict.registrationfee'));

                return $this->redirect($this->generateUrl('fee_registration_show', array(
                    'id' => $id
                )));
            }
        } else {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')
                ->trans('flash.fee.registration.fail.delete'));
        }

        return $this->redirect($this->generateUrl('fee_registration'));
    }

    /**
     * Finds total payables registration fee info
     *
     * @Route("/totalinfo/{tahun}/{gelombang}/{json}", name="fee_registration_totalinfo", defaults={"json"=0})
     */
    public function getFeeInfoTotalAction($tahun, $gelombang, $json)
    {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->findBy(array(
            'tahun' => $tahun,
            'gelombang' => $gelombang
        ));

        $total = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $total += $entity->getNominal();
            }
        }

        if ($json == 1) {
            $string = json_encode(array(
                "biaya" => $total
            ));

            return new Response($string, 200, array(
                'Content-Type' => 'application/json'
            ));
        } else {
            return new Response(number_format($total, 0, ',', '.'));
        }
    }

    /**
     * Finds total payment remains registration fee info
     *
     * @Route("/remains/{tahun}/{gelombang}/{usedfee}/{json}", name="fee_registration_remains")
     */
    public function getFeeInfoRemainAction($tahun, $gelombang, $usedfee, $json = 0)
    {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $usedfee = preg_replace('/,$/', '', $usedfee);
        $querybuilder = $em->createQueryBuilder()
            ->select('biaya')
            ->from('LanggasSisdikBundle:BiayaPendaftaran', 'biaya')
            ->where('biaya.tahun = :tahun')
            ->andWhere('biaya.gelombang = :gelombang')
            ->setParameter("tahun", $tahun)
            ->setParameter("gelombang", $gelombang)
            ->andWhere('biaya.id NOT IN (:usedfee)')
            ->setParameter("usedfee", preg_split('/,/', $usedfee));
        $entities = $querybuilder->getQuery()->getResult();

        $feeamount = 0;
        $counter = 1;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $feeamount += $entity->getNominal();
            }
        }

        if ($json == 1) {
            $string = json_encode(array(
                "biaya" => $feeamount
            ));

            return new Response($string, 200, array(
                'Content-Type' => 'application/json'
            ));
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
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LanggasSisdikBundle:BiayaPendaftaran')->find($id);

        if ($entity instanceof BiayaPendaftaran) {
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
        return $this->createFormBuilder(array(
            'id' => $id
        ))
            ->add('id', 'hidden')
            ->getForm();
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.fee', array(), 'navigations')][$this->get('translator')->trans('links.fee.registration', array(), 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool()
    {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
