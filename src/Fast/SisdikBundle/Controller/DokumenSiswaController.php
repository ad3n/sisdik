<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\Common\Collections\ArrayCollection;

use Fast\SisdikBundle\Form\DokumenType;

use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\JenisDokumenSiswa;
use Fast\SisdikBundle\Util\RuteAsal;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\DokumenSiswa;
use Fast\SisdikBundle\Entity\Dokumen;
use Fast\SisdikBundle\Form\DokumenSiswaType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * DokumenSiswa controller.
 *
 * @Route("/{sid}/dokumen", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class DokumenSiswaController extends Controller
{

    /**
     * Lists all DokumenSiswa entities.
     *
     * @Route("/pendaftar", name="dokumen-pendaftar")
     * @Route("/siswa", name="dokumen-siswa")
     * @Method("GET")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $jenisDokumen = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')
                ->findBy(
                        array(
                            'sekolah' => $siswa->getSekolah(), 'tahun' => $siswa->getTahun(),
                        ), array(
                            'urutan' => 'ASC'
                        ));
        $jumlahJenisDokumen = count($jenisDokumen);
        $idJenisDokumen = array();
        foreach ($jenisDokumen as $jenis) {
            if ($jenis instanceof JenisDokumenSiswa) {
                $idJenisDokumen[] = $jenis->getId();
            }
        }

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:DokumenSiswa', 't')
                ->leftJoin('t.jenisDokumenSiswa', 't2')->where('t.siswa = :siswa')
                ->setParameter('siswa', $siswa)->orderBy('t2.urutan', 'ASC');
        $entities = $querybuilder->getQuery()->getResult();

        $jumlahDokumenTersimpan = count($entities);

        $idDokumenTersimpan = array();
        foreach ($entities as $dokumenSiswa) {
            if ($dokumenSiswa instanceof DokumenSiswa) {
                $idDokumenTersimpan[] = $dokumenSiswa->getJenisDokumenSiswa()->getId();
            }
        }

        $idJenisDokumenForm = array_diff($idJenisDokumen, $idDokumenTersimpan);

        if ($jumlahJenisDokumen == $jumlahDokumenTersimpan && $jumlahDokumenTersimpan > 0) {
            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahJenisDokumen' => $jumlahJenisDokumen,
                    'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan,
                    'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
            );
        } else {
            $dokumenCollection = new ArrayCollection();
            foreach ($idJenisDokumenForm as $id) {
                $dokumen = new DokumenSiswa();
                $dokumen
                        ->setJenisDokumenSiswa(
                                $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id));
                $dokumen->setSiswa($siswa);
                $dokumenCollection->add($dokumen);
            }

            $form = $this
                    ->createForm('collection', $dokumenCollection,
                            array(
                                    'type' => new DokumenSiswaType($this->container), 'required' => true,
                                    'allow_add' => true, 'allow_delete' => true, 'by_reference' => false,
                                    'options' => array(
                                        'widget_control_group' => false, 'label_render' => false,
                                    ), 'label_render' => false, 'widget_control_group' => false,
                            ));

            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahJenisDokumen' => $jumlahJenisDokumen,
                    'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan, 'form' => $form->createView(),
                    'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
            );
        }
    }

    /**
     * Creates a new DokumenSiswa entity.
     *
     * @Route("/pendaftar", name="dokumen-pendaftar_create")
     * @Route("/siswa", name="dokumen-siswa_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:DokumenSiswa:index.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $jenisDokumen = $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')
                ->findBy(
                        array(
                            'sekolah' => $siswa->getSekolah(), 'tahun' => $siswa->getTahun(),
                        ), array(
                            'urutan' => 'ASC'
                        ));
        $jumlahJenisDokumen = count($jenisDokumen);
        $idJenisDokumen = array();
        foreach ($jenisDokumen as $jenis) {
            if ($jenis instanceof JenisDokumenSiswa) {
                $idJenisDokumen[] = $jenis->getId();
            }
        }

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:DokumenSiswa', 't')
                ->leftJoin('t.jenisDokumenSiswa', 't2')->where('t.siswa = :siswa')
                ->setParameter('siswa', $siswa)->orderBy('t2.urutan', 'ASC');
        $entities = $querybuilder->getQuery()->getResult();

        $jumlahDokumenTersimpan = count($entities);

        $idDokumenTersimpan = array();
        foreach ($entities as $dokumenSiswa) {
            if ($dokumenSiswa instanceof DokumenSiswa) {
                $idDokumenTersimpan[] = $dokumenSiswa->getJenisDokumenSiswa()->getId();
            }
        }

        $idJenisDokumenForm = array_diff($idJenisDokumen, $idDokumenTersimpan);

        $dokumenCollection = new ArrayCollection();
        foreach ($idJenisDokumenForm as $id) {
            $dokumen = new DokumenSiswa();
            $dokumen
                    ->setJenisDokumenSiswa(
                            $em->getRepository('FastSisdikBundle:JenisDokumenSiswa')->find($id));
            $dokumen->setSiswa($siswa);
            $dokumenCollection->add($dokumen);
        }

        $form = $this
                ->createForm('collection', $dokumenCollection,
                        array(
                                'type' => new DokumenSiswaType($this->container), 'required' => true,
                                'allow_add' => true, 'allow_delete' => true, 'by_reference' => false,
                                'options' => array(
                                    'widget_control_group' => false, 'label_render' => false,
                                ), 'label_render' => false, 'widget_control_group' => false,
                        ));
        $form->bind($request);

        if ($form->isValid()) {
            foreach ($dokumenCollection as $dokumen) {
                $em->persist($dokumen);
            }
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.dokumen.siswa.tersimpan',
                                            array(
                                                '%siswa%' => $siswa->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            'dokumen-'
                                                    . RuteAsal::ruteAsalSiswaPendaftar(
                                                            $this->getRequest()->getPathInfo()),
                                            array(
                                                'sid' => $siswa->getId()
                                            )));
        }

        return array(
                'entities' => $entities, 'siswa' => $siswa, 'jumlahJenisDokumen' => $jumlahJenisDokumen,
                'jumlahDokumenTersimpan' => $jumlahDokumenTersimpan, 'form' => $form->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Finds and displays a DokumenSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="dokumen-pendaftar_show")
     * @Route("/siswa/{id}", name="dokumen-siswa_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Displays a form to edit an existing DokumenSiswa entity.
     *
     * @Route("/pendaftar/{id}/edit", name="dokumen-pendaftar_edit")
     * @Route("/siswa/{id}/edit", name="dokumen-siswa_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $entity = $em->getRepository('FastSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $editForm = $this->createForm(new DokumenSiswaType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'siswa' => $siswa, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'ruteasal' => RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()),
        );
    }

    /**
     * Edits an existing DokumenSiswa entity.
     *
     * @Route("/pendaftar/{id}", name="dokumen-pendaftar_update")
     * @Route("/siswa/{id}", name="dokumen-siswa_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:DokumenSiswa:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        $entity = $em->getRepository('FastSisdikBundle:DokumenSiswa')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new DokumenSiswaType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.dokumen.siswa.terbarui',
                                            array(
                                                    '%siswa%' => $siswa->getNamaLengkap(),
                                                    '%nama%' => $entity->getJenisDokumenSiswa()
                                                            ->getNamaDokumen()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl(
                                            'dokumen-'
                                                    . RuteAsal::ruteAsalSiswaPendaftar(
                                                            $this->getRequest()->getPathInfo()) . '_show',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'siswa' => $siswa, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a DokumenSiswa entity.
     *
     * @Route("/pendaftar/{id}/delete", name="dokumen-pendaftar_delete")
     * @Route("/siswa/{id}/delete", name="dokumen-siswa_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:DokumenSiswa')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity DokumenSiswa tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.dokumen.siswa.terhapus',
                                                array(
                                                        '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                        '%nama%' => $entity->getJenisDokumenSiswa()
                                                                ->getNamaDokumen()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.dokumen.siswa.gagal.dihapus',
                                            array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap(),
                                                    '%nama%' => $entity->getNama()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl(
                                        'dokumen-'
                                                . RuteAsal::ruteAsalSiswaPendaftar(
                                                        $this->getRequest()->getPathInfo()),
                                        array(
                                            'sid' => $sid,
                                        )));
    }

    /**
     * Creates a form to delete a DokumenSiswa entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        if (RuteAsal::ruteAsalSiswaPendaftar($this->getRequest()->getPathInfo()) == 'pendaftar') {
            $menu['headings.academic']['links.registration']->setCurrent(true);
        } else {
            $menu['headings.academic']['links.data.student']->setCurrent(true);
        }
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
