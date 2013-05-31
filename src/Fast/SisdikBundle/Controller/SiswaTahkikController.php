<?php

namespace Fast\SisdikBundle\Controller;
use Fast\SisdikBundle\Form\SiswaTahkikSearchType;

use Fast\SisdikBundle\Entity\Referensi;
use Fast\SisdikBundle\Entity\PanitiaPendaftaran;
use Fast\SisdikBundle\Form\SiswaApplicantSearchType;
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

        $user = $this->getUser();

        $searchform = $this->createForm(new SiswaTahkikSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Siswa', 't')
                ->leftJoin('t.tahun', 't2')->leftJoin('t.gelombang', 't3')->where('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->addOrderBy('t3.urutan', 'DESC')
                ->addOrderBy('t.nomorUrutPendaftaran', 'DESC');

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            $searchparam = '';
            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('t2.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());

                $searchparam = " tahun = '{$searchdata['tahun']->getId()}' ";
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder->andWhere('t.namaLengkap LIKE :namalengkap');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");

                if (is_numeric($searchdata['searchkey'])) {
                    $dql = "SELECT t FROM FastSisdikBundle:Siswa t "
                            . " LEFT JOIN t.pembayaranPendaftaran t2 "
                            . " WHERE t.nomorPendaftaran = CASE WHEN t2.siswa IS NOT NULL THEN '{$searchdata['searchkey']}' ELSE '0' END"
                            . ($searchparam != '' ? " AND t.$searchparam" : "");
                    $query = $em->createQuery($dql);
                }
            }
        }

        $paginator = $this->get('knp_paginator');
        if (is_numeric($searchdata['searchkey'])) {
            $pagination = $paginator
                    ->paginate($query->getResult(), $this->getRequest()->query->get('page', 1));
        } else {
            $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));
        }

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'tahunaktif' => $this->getTahunPanitiaAktif(),
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
        $editForm->bind($request);

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

    private function getTahunPanitiaAktif() {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $qb0 = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:PanitiaPendaftaran', 't')
                ->leftJoin('t.tahun', 't2')->where('t.sekolah = :sekolah')->andWhere('t.aktif = 1')
                ->orderBy('t2.tahun', 'DESC')->setParameter('sekolah', $sekolah->getId())->setMaxResults(1);
        $results = $qb0->getQuery()->getResult();
        foreach ($results as $entity) {
            if (is_object($entity) && $entity instanceof PanitiaPendaftaran) {
                $tahunaktif = $entity->getTahun()->getTahun();
            }
        }

        return $tahunaktif;
    }

    private function verifyTahun($tahun) {
        if ($this->getTahunPanitiaAktif() != $tahun) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('cannot.alter.tahkik.inactive.year'));
        }
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.pendaftaran']['links.tahkik']->setCurrent(true);
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
