<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\PersonilSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Tahun;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\TranslationBundle\Annotation\Ignore;

/**
 * @Route("/keterangan-pembayaran-rutin")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA')")
 */
class KeteranganPembayaranRutinController extends Controller
{
    /**
     * @Route("/", name="keterangan_pembayaran_rutin")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $siswaTotal = $em->createQueryBuilder()
            ->select('COUNT(siswa.id)')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $siswaBerketeranganTotal = $em->createQueryBuilder()
            ->select('COUNT(siswa.id)')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere("siswa.keteranganPembayaranRutin IS NOT NULL")
            ->andWhere("siswa.keteranganPembayaranRutin <> ''")
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $searchform = $this->createForm('sisdik_carisiswa');

        $querybuilder = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->leftJoin('siswa.tahun', 'tahun')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere("siswa.keteranganPembayaranRutin IS NOT NULL")
            ->andWhere("siswa.keteranganPembayaranRutin <> ''")
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
            ->orderBy('tahun.tahun', 'DESC')
            ->addOrderBy('siswa.nomorIndukSistem', 'DESC')
        ;

        $tampilkanTercari = false;

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] instanceof Tahun) {
                $querybuilder
                    ->andWhere('siswa.tahun = :tahun')
                    ->setParameter('tahun', $searchdata['tahun'])
                ;

                $tampilkanTercari = true;
            }

            if ($searchdata['searchkey'] != '') {
                $querybuilder
                    ->andWhere("siswa.namaLengkap LIKE :searchkey "
                        ." OR siswa.nomorInduk LIKE :searchkey "
                        ." OR siswa.nomorIndukSistem LIKE :searchkey"
                        ." OR siswa.keteranganPembayaranRutin LIKE :searchkey")
                    ->setParameter('searchkey', "%{$searchdata['searchkey']}%")
                ;

                $tampilkanTercari = true;
            }
        }

        $qbPencarian = clone $querybuilder;
        $qbPencarian->select('COUNT(siswa.id)');

        $siswaTercari = $qbPencarian->getQuery()->getSingleScalarResult();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'siswaTotal' => $siswaTotal,
            'siswaBerketeranganTotal' => $siswaBerketeranganTotal,
            'tampilkanTercari' => $tampilkanTercari,
            'siswaTercari' => $siswaTercari,
        ];
    }

    /**
     * @Route("/", name="keterangan_pembayaran_rutin__create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:KeteranganPembayaranRutin:new.html.twig")
     */
    public function createAction()
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createCreateForm();
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $keterangan = $form->get('keteranganPembayaranRutin')->getData();
            $personilSiswa = $form->get('siswa')->getData();

            if ($keterangan !== '') {
                foreach ($personilSiswa as $personil) {
                    if ($personil instanceof PersonilSiswa) {
                        if ($personil->getId() !== null) {
                            $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($personil->getId());
                            if ($siswa instanceof Siswa) {
                                if ($this->get('security.authorization_checker')->isGranted('create', $siswa) === false) {
                                    throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
                                }

                                $siswa->setKeteranganPembayaranRutin($keterangan);
                                $em->persist($siswa);
                            }
                        }
                    }
                }
            }

            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.keterangan.pembayaran.berulang.tersimpan'))
            ;

            return $this->redirect($this->generateUrl('keterangan_pembayaran_rutin'));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/new", name="keterangan_pembayaran_rutin__new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $form = $this->createCreateForm();

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="keterangan_pembayaran_rutin__edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        if (!$entity instanceof Siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createEditForm($entity);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="keterangan_pembayaran_rutin__update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:KeteranganPembayaranRutin:edit.html.twig")
     */
    public function updateAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        if (!$entity instanceof Siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createEditForm($entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            $entity->setWaktuUbah(new \DateTime());

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.keterangan.pembayaran.berulang.terbarui', [
                    '%siswa%' => $entity->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl('keterangan_pembayaran_rutin__edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/delete", name="keterangan_pembayaran_rutin__delete")
     * @Method("GET")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Siswa')->find($id);

        if (!$entity instanceof Siswa) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('delete', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity->setKeteranganPembayaranRutin(null);

        $em->persist($entity);
        $em->flush();

        $this
            ->get('session')
            ->getFlashBag()
            ->add('success', $this->get('translator')->trans('flash.keterangan.pembayaran.berulang.berhasil.dihapus', [
                '%siswa%' => $entity->getNamaLengkap(),
            ]))
        ;

        return $this->redirect($this->generateUrl('keterangan_pembayaran_rutin'));
    }

    /**
     * @Route("/ajax-ambil-siswa", name="keterangan_pembayaran_rutin__siswa")
     */
    public function getSiswa()
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $filter = $this->getRequest()->query->get('filter');

        $results = $em->createQueryBuilder()
            ->select('siswa')
            ->from('LanggasSisdikBundle:Siswa', 'siswa')
            ->where('siswa.sekolah = :sekolah')
            ->andWhere('siswa.calonSiswa = :calon')
            ->andWhere(
                'siswa.namaLengkap LIKE :filter'
                .' OR siswa.nomorIndukSistem LIKE :filter2'
                .' OR siswa.nomorInduk LIKE :filter2'
            )
            ->orderBy('siswa.namaLengkap', 'ASC')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('calon', false)
            ->setParameter('filter', "%$filter%")
            ->setParameter('filter2', $filter)
            ->getQuery()
            ->getResult()
        ;

        $retval = [];
        foreach ($results as $siswa) {
            if ($siswa instanceof Siswa) {
                $retval[] = [
                    'source' => 'namaSiswa',
                    'target' => 'id',
                    'id' => $siswa->getId(),
                    'label' =>/** @Ignore */ $siswa->getNamaLengkap()." ({$siswa->getNomorIndukSistem()}, {$siswa->getTahun()->getTahun()})",
                    'value' => $siswa->getNamaLengkap()." ({$siswa->getNomorIndukSistem()}, {$siswa->getTahun()->getTahun()})",
                ];
            }
        }

        if (count($retval) == 0) {
            $label = $this->get('translator')->trans("label.siswa.tak.ditemukan");
            $retval[] = [
                'source' => 'namaSiswa',
                'target' => 'id',
                'id' => -999,
                'label' =>/** @Ignore */ $label,
                'value' => $label,
            ];
        }

        return new Response(json_encode($retval), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @return Form
     */
    private function createCreateForm()
    {
        $form = $this->createFormBuilder(null, [
                'action' => $this->generateUrl('keterangan_pembayaran_rutin__create'),
                'method' => 'POST',
            ])
            ->add('keteranganPembayaranRutin', 'textarea', [
                'label' => 'label.keterangan.pembayaran.berulang',
                'required' => true,
            ])
            ->add('siswa', 'collection', [
                'type' => 'sisdik_personilsiswa',
                'label' => 'label.daftar.siswa',
                'label_render' => true,
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => true,
                'prototype' => true,
                'widget_add_btn' => [
                    'label' => 'label.tambah.siswa.penerima',
                    'attr' => [
                        'class' => 'btn',
                    ],
                    'icon' => 'plus-sign',
                ],
                'options' => [
                    'label_render' => false,
                    'widget_remove_btn' => [
                        'label' => 'label.delete',
                        'attr' => [
                            'class' => 'btn',
                        ],
                        'icon' => 'trash',
                        'wrapper_div' => false,
                        'horizontal_wrapper_div' => false,
                    ],
                ],
            ])
            ->getForm()
        ;

        return $form;
    }

    /**
     * @param  Siswa $entity
     * @return Form
     */
    private function createEditForm(Siswa $entity)
    {
        $form = $this->createFormBuilder($entity, [
                'action' => $this->generateUrl('keterangan_pembayaran_rutin__update', [
                    'id' => $entity->getId(),
                ]),
                'method' => 'POST',
            ])
            ->add('keteranganPembayaranRutin', 'textarea', [
                'label' => 'label.keterangan.pembayaran.berulang',
                'required' => false,
            ])
            ->getForm()
        ;

        return $form;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.keterangan.pembayaran.berulang', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
