<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaTahkikSearchType;
use Fast\SisdikBundle\Entity\Referensi;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Form\SiswaApplicantType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Siswa tahkik controller.
 *
 * @Route("/tahkik")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_KETUA_PANITIA_PSB')")
 */
class SiswaTahkikController extends Controller
{
    /**
     * Menampilkan form untuk mencari calon siswa yang akan ditahkikkan
     *
     * @Route("/", name="tahkik")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $panitiaAktif = $this->getPanitiaAktif();
        if (!is_array($panitiaAktif) || count($panitiaAktif) <= 0) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.tidak.ada.panitia.pendaftaran'));
        }

        $searchform = $this->createForm(new SiswaTahkikSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.gelombang', 'gelombang')
                ->leftJoin('siswa.orangtuaWali', 'orangtua')->where('siswa.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->andWhere('tahun.id = ?1')
                ->setParameter(1, $panitiaAktif[2])->addOrderBy('gelombang.urutan', 'DESC')
                ->addOrderBy('siswa.nomorUrutPendaftaran', 'DESC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                'siswa.namaLengkap LIKE :namalengkap OR siswa.nomorPendaftaran LIKE :nomor '
                                        . ' OR siswa.keterangan LIKE :keterangan OR siswa.alamat LIKE :alamat '
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomor', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('keterangan', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('alamat', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'panitiaAktif' => $panitiaAktif,
        );
    }

    /**
     * Finds and displays a Siswa tahkik entity.
     *
     * @Route("/{id}", name="tahkik_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'tahunaktif' => $this->getTahunPanitiaAktif(),
        );
    }

    /**
     * Edits an existing Siswa tahkik entity.
     *
     * @Route("/{id}", name="tahkik_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:SiswaTahkik:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Siswa')->find($id);

        $this->verifyTahun($entity->getTahun()->getTahun());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new SiswaApplicantType($this->container, 'edit'), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {

                if ($editForm['referensi']->getData() === null && $editForm['namaReferensi']->getData() != "") {
                    $referensi = new Referensi();
                    $referensi->setNama($editForm['namaReferensi']->getData());
                    $referensi->setSekolah($sekolah);
                    $entity->setReferensi($referensi);
                }

                // force unit of work detect entity 'changes'
                // possible problem source: too many objects handled by doctrine
                $entity->setWaktuUbah(new \DateTime());

                $entity->setDiubahOleh($this->getUser());

                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.tahkik.updated',
                                                array(
                                                    '%name%' => $entity->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.tahkik');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('tahkik_edit',
                                            array(
                                                'id' => $id, 'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    /**
     * Mencari panitia pendaftaran aktif
     * Fungsi ini mengembalikan array berisi
     * index 0: daftar id panitia aktif
     * index 1: id ketua panitia aktif
     * index 2: id tahun panitia aktif
     * index 3: string tahun panitia aktif
     *
     * @return array panitiaaktif
     */
    public function getPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('panitia')
                ->from('FastSisdikBundle:PanitiaPendaftaran', 'panitia')->leftJoin('panitia.tahun', 'tahun')
                ->where('panitia.sekolah = :sekolah')->andWhere('panitia.aktif = 1')
                ->orderBy('tahun.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())
                ->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        $panitiaaktif = array();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $panitiaaktif[0] = $entity->getPanitia();
                $panitiaaktif[1] = $entity->getKetuaPanitia()->getId();
                $panitiaaktif[2] = $entity->getTahun()->getId();
                $panitiaaktif[3] = $entity->getTahun()->getTahun();
            }
        }

        return $panitiaaktif;
    }

    private function verifyTahun($tahun) {
        if ($this->getTahunPanitiaAktif() != $tahun) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('cannot.alter.tahkik.inactive.year'));
        }
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        // $menu['headings.pendaftaran']['links.tahkik']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
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
