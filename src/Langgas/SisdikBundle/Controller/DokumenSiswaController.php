<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\Common\Collections\ArrayCollection;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\JenisDokumenSiswa;
use Langgas\SisdikBundle\Util\RuteAsal;
use Langgas\SisdikBundle\Entity\DokumenSiswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/{sid}/dokumen", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class DokumenSiswaController extends Controller
{
    /**
     * @Route("/pendaftar", name="dokumen-pendaftar")
     * @Route("/siswa", name="dokumen-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $jenisDokumen = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')
            ->findBy([
                'sekolah' => $siswa->getSekolah(),
                'tahun' => $siswa->getTahun(),
            ], [
                'urutan' => 'ASC'
            ])
        ;

        $jumlahJenisDokumen = count($jenisDokumen);

        $idJenisDokumen = [];
        foreach ($jenisDokumen as $jenis) {
            if ($jenis instanceof JenisDokumenSiswa) {
                $idJenisDokumen[] = $jenis->getId();
            }
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('dokumenSiswa')
            ->from('LanggasSisdikBundle:DokumenSiswa', 'dokumenSiswa')
            ->leftJoin('dokumenSiswa.jenisDokumenSiswa', 'jenisDokumenSiswa')
            ->where('dokumenSiswa.siswa = :siswa')
            ->setParameter('siswa', $siswa)
            ->orderBy('jenisDokumenSiswa.urutan', 'ASC')
        ;
        $entities = $querybuilder->getQuery()->getResult();

        $jumlahDokumenTersimpan = count($entities);

        $idDokumenTersimpan = [];
        foreach ($entities as $dokumenSiswa) {
            if ($dokumenSiswa instanceof DokumenSiswa) {
                $idDokumenTersimpan[] = $dokumenSiswa->getJenisDokumenSiswa()->getId();
            }
        }

        $idJenisDokumenForm = array_diff($idJenisDokumen, $idDokumenTersimpan);

        if ($jumlahJenisDokumen == $jumlahDokumenTersimpan && $jumlahDokumenTersimpan > 0) {
            return [
                'entities' => $entities,
                'siswa' => $siswa,
                'jumlahJenisDokumen' => $jumlahJenisDokumen,
                'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan,
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
            ];
        } else {
            $dokumenCollection = new ArrayCollection;

            foreach ($idJenisDokumenForm as $id) {
                $dokumen = new DokumenSiswa;
                $dokumen->setJenisDokumenSiswa($em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id));
                $dokumen->setSiswa($siswa);
                $dokumenCollection->add($dokumen);
            }

            $form = $this->createForm('collection', $dokumenCollection, [
                'type' => 'sisdik_dokumensiswa',
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'options' => [
                    'widget_form_group' => false,
                    'label_render' => false,
                    'widget_remove_btn' => false,
                ],
                'label_render' => false,
            ]);

            return [
                'entities' => $entities,
                'siswa' => $siswa,
                'jumlahJenisDokumen' => $jumlahJenisDokumen,
                'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan,
                'form' => $form->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
            ];
        }
    }

    /**
     * @Route("/pendaftar", name="dokumen-pendaftar_create")
     * @Route("/siswa", name="dokumen-siswa_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:DokumenSiswa:index.html.twig")
     */
    public function createAction(Request $request, $sid)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $jenisDokumen = $em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')
            ->findBy([
                'sekolah' => $siswa->getSekolah(),
                'tahun' => $siswa->getTahun(),
            ], [
                'urutan' => 'ASC',
            ])
        ;

        $jumlahJenisDokumen = count($jenisDokumen);

        $idJenisDokumen = [];
        foreach ($jenisDokumen as $jenis) {
            if ($jenis instanceof JenisDokumenSiswa) {
                $idJenisDokumen[] = $jenis->getId();
            }
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('dokumenSiswa')
            ->from('LanggasSisdikBundle:DokumenSiswa', 'dokumenSiswa')
            ->leftJoin('dokumenSiswa.jenisDokumenSiswa', 'jenisDokumenSiswa')
            ->where('dokumenSiswa.siswa = :siswa')
            ->setParameter('siswa', $siswa)
            ->orderBy('jenisDokumenSiswa.urutan', 'ASC')
        ;
        $entities = $querybuilder->getQuery()->getResult();

        $jumlahDokumenTersimpan = count($entities);

        $idDokumenTersimpan = [];
        foreach ($entities as $dokumenSiswa) {
            if ($dokumenSiswa instanceof DokumenSiswa) {
                $idDokumenTersimpan[] = $dokumenSiswa->getJenisDokumenSiswa()->getId();
            }
        }

        $idJenisDokumenForm = array_diff($idJenisDokumen, $idDokumenTersimpan);

        $dokumenCollection = new ArrayCollection;
        foreach ($idJenisDokumenForm as $id) {
            $dokumen = new DokumenSiswa;
            $dokumen->setJenisDokumenSiswa($em->getRepository('LanggasSisdikBundle:JenisDokumenSiswa')->find($id));
            $dokumen->setSiswa($siswa);
            $dokumenCollection->add($dokumen);
        }

        $form = $this->createForm('collection', $dokumenCollection, [
            'type' => 'sisdik_dokumensiswa',
            'required' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'options' => [
                'widget_form_group' => false,
                'label_render' => false,
                'widget_remove_btn' => false,
            ],
            'label_render' => false,
        ]);

        $form->submit($request);

        if ($form->isValid()) {
            foreach ($dokumenCollection as $dokumen) {
                $em->persist($dokumen);
            }

            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.dokumen.siswa.tersimpan', [
                    '%siswa%' => $siswa->getNamaLengkap(),
                ]))
            ;

            return $this->redirect($this->generateUrl('dokumen-' . RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()), [
                'sid' => $siswa->getId(),
            ]));
        }

        return [
            'entities' => $entities,
            'siswa' => $siswa,
            'jumlahJenisDokumen' => $jumlahJenisDokumen,
            'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan,
            'form' => $form->createView(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}", name="dokumen-pendaftar_show")
     * @Route("/siswa/{id}", name="dokumen-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/edit", name="dokumen-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="dokumen-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $entity = $em->getRepository('LanggasSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_dokumensiswa', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'siswa' => $siswa,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        ];
    }

    /**
     * @Route("/pendaftar/{id}", name="dokumen-pendaftar_update")
     * @Route("/siswa/{id}", name="dokumen-siswa_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:DokumenSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $entity = $em->getRepository('LanggasSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_dokumensiswa', $entity);

        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.dokumen.siswa.terbarui', [
                    '%siswa%' => $siswa->getNamaLengkap(),
                    '%nama%' => $entity->getJenisDokumenSiswa()->getNamaDokumen(),
                ]))
            ;

            return $this->redirect($this->generateUrl('dokumen-' . RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) . '_show', [
                'sid' => $sid,
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'siswa' => $siswa,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/pendaftar/{id}/delete", name="dokumen-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="dokumen-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id)
    {
        $form = $this->createDeleteForm($id);

        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $entity = $em->getRepository('LanggasSisdikBundle:DokumenSiswa')->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.dokumen.siswa.terhapus', [
                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                        '%nama%' => $entity->getJenisDokumenSiswa()->getNamaDokumen(),
                    ]))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.dokumen.siswa.gagal.dihapus', [
                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                    '%nama%' => $entity->getNama(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('dokumen-' . RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()), [
            'sid' => $sid,
        ]));
    }

    /**
     * @param  integer                     $id
     * @return Symfony\Component\Form\Form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        if (RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar') {
            $menu[$translator->trans('headings.pendaftaran', [], 'navigations')][$translator->trans('links.registration', [], 'navigations')]->setCurrent(true);
        } else {
            $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.siswa', [], 'navigations')]->setCurrent(true);
        }
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
