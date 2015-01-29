<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Gelombang;
use Langgas\SisdikBundle\Entity\LayananSms;
use Langgas\SisdikBundle\Entity\OrangtuaWali;
use Langgas\SisdikBundle\Entity\PanitiaPendaftaran;
use Langgas\SisdikBundle\Entity\PilihanLayananSms;
use Langgas\SisdikBundle\Entity\Referensi;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\SekolahAsal;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Util\Messenger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/pendaftar")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_PANITIA_PSB')")
 */
class SiswaPendaftarController extends Controller
{
    /**
     * @Route("/", name="applicant")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();
        $searchkey = '';

        $searchform = $this->createForm('sisdik_caripendaftar');

        $qbtotal = $em->createQueryBuilder()
            ->select('COUNT(siswa.id)')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = ?1')
            ->setParameter('calon', true)
            ->setParameter('sekolah', $sekolah)
            ->setParameter(1, $panitiaAktif[2])
        ;
        $pendaftarTotal = $qbtotal->getQuery()->getSingleScalarResult();

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->leftJoin('siswa.gelombang', 'gelombang')
            ->leftJoin('siswa.sekolahAsal', 'sekolahAsal')
            ->leftJoin('siswa.orangtuaWali', 'orangtua')
            ->where('siswa.calonSiswa = :calon')
            ->andWhere('orangtua.aktif = :ortuaktif')
            ->andWhere('siswa.sekolah = :sekolah')
            ->andWhere('tahun.id = ?1')
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('gelombang.urutan', 'DESC')
            ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC')
            ->setParameter('calon', true)
            ->setParameter('ortuaktif', true)
            ->setParameter('sekolah', $sekolah)
            ->setParameter(1, $panitiaAktif[2])
        ;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $tampilkanTercari = false;

            if ($searchdata['gelombang'] instanceof Gelombang) {
                $querybuilder
                    ->andWhere('siswa.gelombang = :gelombang')
                    ->setParameter('gelombang', $searchdata['gelombang'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                    ->andWhere('siswa.namaLengkap LIKE :namalengkap'
                        .' OR siswa.nomorPendaftaran LIKE :nomor '
                        .' OR siswa.keterangan LIKE :keterangan '
                        .' OR siswa.alamat LIKE :alamat '
                        .' OR orangtua.nama LIKE :namaortu '
                        .' OR orangtua.ponsel LIKE :ponselortu ')
                    ->setParameter('namalengkap', "%{$searchdata['searchkey']}%")
                    ->setParameter('nomor', "%{$searchdata['searchkey']}%")
                    ->setParameter('keterangan', "%{$searchdata['searchkey']}%")
                    ->setParameter('alamat', "%{$searchdata['searchkey']}%")
                    ->setParameter('namaortu', "%{$searchdata['searchkey']}%")
                    ->setParameter('ponselortu', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }
        }

        $pendaftarTercari = count($querybuilder->getQuery()->getResult());

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'panitiaAktif' => $panitiaAktif,
            'pendaftarTotal' => $pendaftarTotal,
            'pendaftarTercari' => $pendaftarTercari,
            'tampilkanTercari' => $tampilkanTercari,
            'searchkey' => $searchkey,
        ];
    }

    /**
     * @Route("/", name="applicant_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaPendaftar:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $entity = new Siswa();

        $form = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'new',
        ]);

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity->setCalonSiswa(true);

            $qbmaxnum = $em->createQueryBuilder()
                ->select('MAX(siswa.nomorUrutPendaftaran)')
                ->from('LanggasSisdikBundle:Siswa', 'siswa')
                ->where("siswa.tahun = :tahun")
                ->setParameter('tahun', $entity->getTahun())
                ->andWhere('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $entity->getSekolah())
            ;
            $nomormax = intval($qbmaxnum->getQuery()->getSingleScalarResult());
            $nomormax++;

            $entity->setNomorUrutPendaftaran($nomormax);
            $entity->setNomorPendaftaran($entity->getTahun()->getTahun().$nomormax);

            if ($form['adaReferensi']->getData() === true && $form['referensi']->getData() === null && $form['namaReferensi']->getData() != "") {
                $referensi = new Referensi();
                $referensi->setNama($form['namaReferensi']->getData());
                $referensi->setSekolah($sekolah);

                $entity->setReferensi($referensi);
            }

            try {
                $em->persist($entity);
                $em->flush();

                $pilihanLayananSms = $em->getRepository('LanggasSisdikBundle:PilihanLayananSms')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                        'jenisLayanan' => 'a-pendaftaran-tercatat',
                    ])
                ;

                $vendorSekolah = $em->getRepository('LanggasSisdikBundle:VendorSekolah')
                    ->findOneBy([
                        'sekolah' => $sekolah,
                    ])
                ;

                if ($pilihanLayananSms instanceof PilihanLayananSms) {
                    if ($pilihanLayananSms->getStatus()) {
                        $layananSms = $em->getRepository('LanggasSisdikBundle:LayananSms')
                            ->findOneBy([
                                'sekolah' => $sekolah,
                                'jenisLayanan' => 'a-pendaftaran-tercatat',
                            ])
                        ;
                        if ($layananSms instanceof LayananSms) {
                            $tekstemplate = $layananSms->getTemplatesms()->getTeks();

                            $namaOrtuWali = "";
                            $ponselOrtuWali = "";
                            $orangtuaWaliAktif = $entity->getOrangtuaWaliAktif();
                            if ($orangtuaWaliAktif instanceof OrangtuaWali) {
                                $namaOrtuWali = $orangtuaWaliAktif->getNama();
                                $ponselOrtuWali = $orangtuaWaliAktif->getPonsel();
                            }

                            $tekstemplate = str_replace("%nama-ortuwali%", $namaOrtuWali, $tekstemplate);
                            $tekstemplate = str_replace("%nama-calonsiswa%", $entity->getNamaLengkap(), $tekstemplate);
                            $tekstemplate = str_replace("%tahun%", $entity->getTahun()->getTahun(), $tekstemplate);
                            $tekstemplate = str_replace("%gelombang%", $entity->getGelombang()->getNama(), $tekstemplate);

                            if ($ponselOrtuWali != "") {
                                $nomorponsel = preg_split("/[\s,\/]+/", $ponselOrtuWali);
                                foreach ($nomorponsel as $ponsel) {
                                    $messenger = $this->get('sisdik.messenger');
                                    if ($messenger instanceof Messenger) {
                                        if ($vendorSekolah instanceof VendorSekolah) {
                                            if ($vendorSekolah->getJenis() == 'khusus') {
                                                $messenger->setUseVendor(true);
                                                $messenger->setVendorURL($vendorSekolah->getUrlPengirimPesan());
                                            }
                                        }
                                        $messenger->setPhoneNumber($ponsel);
                                        $messenger->setMessage($tekstemplate);
                                        $messenger->sendMessage($sekolah);
                                    }
                                }
                            }
                        }
                    }
                }

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.applicant.inserted', [
                        '%name%' => $entity->getNamaLengkap(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('applicant_show', [
                'id' => $entity->getId(),
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/new", name="applicant_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $entity = new Siswa();
        $orangtuaWali = new OrangtuaWali();
        $entity->getOrangtuaWali()->add($orangtuaWali);

        $form = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'new',
        ]);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="applicant_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'panitiaAktif' => $panitiaAktif,
        ];
    }

    /**
     * @Route("/{id}/edit", name="applicant_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'edit',
        ]);

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="applicant_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaPendaftar:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        $editForm = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'edit',
        ]);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                if ($editForm['referensi']->getData() === null && $editForm['namaReferensi']->getData() != "") {
                    $referensi = new Referensi();
                    $referensi->setNama($editForm['namaReferensi']->getData());
                    $referensi->setSekolah($sekolah);

                    $entity->setReferensi($referensi);
                }

                if ($editForm['sekolahAsal']->getData() === null && $editForm['namaSekolahAsal']->getData() != "") {
                    $sekolahAsal = new SekolahAsal();
                    $sekolahAsal->setNama($editForm['namaSekolahAsal']->getData());
                    $sekolahAsal->setSekolah($sekolah);

                    $entity->setSekolahAsal($sekolahAsal);
                }

                // force unit of work detect entity 'changes'
                // possible problem source: too many objects handled by doctrine
                $entity->setWaktuUbah(new \DateTime());

                $entity->setDiubahOleh($this->getUser());

                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.applicant.updated', [
                        '%name%' => $entity->getNamaLengkap(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('applicant_edit', [
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
     * Handling HTTP RAW DATA sent from jpegcam library
     *
     * @Route("/webcamupload/{tahun}", name="applicant_webcam_uploadhandler")
     * @Method("POST")
     */
    public function webcamUploadHandlerAction(Request $request, $tahun)
    {
        $sekolah = $this->getSekolah();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $fs = new Filesystem();
        if (!$fs->exists(Siswa::WEBCAMPHOTO_DIR.$sekolah->getId().'/'.$tahun)) {
            $fs->mkdir(Siswa::WEBCAMPHOTO_DIR.$sekolah->getId().'/'.$tahun);
        }

        $filename = date('YmdHis').'.jpg';
        $targetfile = Siswa::WEBCAMPHOTO_DIR.$sekolah->getId().'/'.$tahun.'/'.$filename;

        $output = $filename;

        $result = file_put_contents($targetfile, file_get_contents('php://input'));
        if (!$result) {
            $output = $this->get('translator')->trans('errorinfo.cannot.writefile', [
                'filename' => $filename,
            ]);
        }

        return new Response($output, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * @Route("/{id}/editregphoto", name="applicant_editregphoto")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:SiswaPendaftar:editregphoto.html.twig")
     */
    public function editRegistrationPhotoAction($id)
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'editregphoto',
        ]);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/updateregphoto", name="applicant_updateregphoto")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaPendaftar:editregphoto.html.twig")
     */
    public function updateRegistrationPhotoAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_siswapendaftar', $entity, [
            'tahun_aktif' => $panitiaAktif[2],
            'mode' => 'editregphoto',
        ]);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.applicant.regphoto.updated', [
                        '%name%' => $entity->getNamaLengkap(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('applicant_show', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Menampilkan form konfirmasi untuk menghapus pendaftar
     * Menghapus hanya bisa dilakukan terhadap data yang belum memiliki data pembayaran
     *
     * @Route("/{id}/remove", name="applicant_delete_confirm")
     * @Template("LanggasSisdikBundle:SiswaPendaftar:delete.confirm.html.twig")
     * @Secure(roles="ROLE_ADMIN, ROLE_KETUA_PANITIA_PSB")
     */
    public function deleteConfirmAction($id)
    {
        $this->setCurrentMenu();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if (!((is_array($panitiaAktif[0]) && in_array($this->getUser()->getId(), $panitiaAktif[0])) || $panitiaAktif[1] == $this->getUser()->getId())) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.register.as.committee'));
        }

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);
        if (!$entity && !$entity instanceof Siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('delete', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        if (count($entity->getPembayaranPendaftaran()) > 0) {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('alert.pendaftar.sudah.melakukan.pembayaran'))
            ;

            return $this->redirect($this->generateUrl('applicant_show', [
                'id' => $id,
            ]));
        }

        $form = $this->createForm('sisdik_confirm', null, [
            'sessiondata' => uniqid(),
        ]);

        $request = $this->getRequest();
        if ($request->getMethod() == "POST") {
            $form->submit($request);
            if ($form->isValid()) {
                try {
                    $em->remove($entity);
                    $em->flush();

                    $this
                        ->get('session')
                        ->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.applicant.deleted', [
                            '%name%' => $entity->getNamaLengkap(),
                        ]))
                    ;
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.delete.restrict');
                    throw new DBALException($message);
                }

                return $this->redirect($this->generateUrl('applicant'));
            } else {
                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.applicant.fail.delete', [
                        '%name%' => $entity->getNamaLengkap(),
                    ]))
                ;
            }
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
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

    /**
     * Mencari panitia pendaftaran aktif
     *
     * Mengembalikan array berisi
     * index 0: daftar id panitia aktif,
     * index 1: id ketua panitia aktif,
     * index 2: id tahun panitia aktif,
     * index 3: string tahun panitia aktif.
     *
     * @return array panitiaaktif
     */
    private function getPanitiaAktif()
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $entityPanitiaAktif = $em->getRepository('LanggasSisdikBundle:PanitiaPendaftaran')->findOneBy([
            'sekolah' => $sekolah,
            'aktif' => 1,
        ]);

        $panitia = [];
        if (is_object($entityPanitiaAktif) && $entityPanitiaAktif instanceof PanitiaPendaftaran) {
            $panitia[0] = $entityPanitiaAktif->getPanitia();
            $panitia[1] = $entityPanitiaAktif->getKetuaPanitia()->getId();
            $panitia[2] = $entityPanitiaAktif->getTahun()->getId();
            $panitia[3] = $entityPanitiaAktif->getTahun()->getTahun();
        }

        return $panitia;
    }

    private function verifyTahun($tahun)
    {
        $panitiaAktif = $this->getPanitiaAktif();

        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        if ($panitiaAktif[3] != $tahun) {
            throw new AccessDeniedException($this->get('translator')->trans('cannot.alter.applicant.inactive.year'));
        }
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.registration', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
