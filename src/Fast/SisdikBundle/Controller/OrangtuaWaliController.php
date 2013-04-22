<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\OrangtuaWali;
use Fast\SisdikBundle\Form\OrangtuaWaliType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * OrangtuaWali controller.
 *
 * @Route("/{sid}/parentguard", requirements={"sid"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH', 'ROLE_WALI_KELAS', 'ROLE_PANITIA_PSB')")
 */
class OrangtuaWaliController extends Controller
{
    /**
     * Lists all OrangtuaWali entities.
     *
     * @Route("/", name="parentguard")
     * @Template()
     */
    public function indexAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:OrangtuaWali', 't')
                ->where('t.siswa = :siswa')->orderBy('t.aktif', 'DESC')->setParameter('siswa', $sid);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->get('request')->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Mengaktifkan status orang tua wali, dan menonaktifkan yang lain
     *
     * @Route("/{id}/activate", name="parentguard_activate")
     */
    public function activateAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $query = $em->createQueryBuilder()->update('FastSisdikBundle:OrangtuaWali', 't')->set('t.aktif', 0)
                ->where('t.siswa = :siswa')->setParameter('siswa', $sid)->getQuery();
        $query->execute();

        $entity->setAktif(true);
        $em->persist($entity);
        $em->flush();

        return $this
                ->redirect(
                        $this
                                ->generateUrl('parentguard',
                                        array(
                                            'sid' => $sid
                                        )));
    }

    /**
     * Finds and displays a OrangtuaWali entity.
     *
     * @Route("/{id}/show", name="parentguard_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new OrangtuaWali entity.
     *
     * @Route("/new", name="parentguard_new")
     * @Template()
     */
    public function newAction($sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Creates a new OrangtuaWali entity.
     *
     * @Route("/create", name="parentguard_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:new.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new OrangtuaWali();
        $form = $this->createForm(new OrangtuaWaliType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);
            $entity->setSiswa($siswa);
            $entity->setAktif(false);

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.tersimpan',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('parentguard_show',
                                            array(
                                                'sid' => $sid, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Displays a form to edit an existing OrangtuaWali entity.
     *
     * @Route("/{id}/edit", name="parentguard_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid)
        );
    }

    /**
     * Edits an existing OrangtuaWali entity.
     *
     * @Route("/{id}/update", name="parentguard_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:OrangtuaWali:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new OrangtuaWaliType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.terbarui',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('parentguard_edit',
                                            array(
                                                'sid' => $sid, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'siswa' => $em->getRepository('FastSisdikBundle:Siswa')->find($sid),
        );
    }

    /**
     * Deletes a OrangtuaWali entity.
     *
     * @Route("/{id}/delete", name="parentguard_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $sid, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:OrangtuaWali')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity OrangtuaWali tak ditemukan.');
            }

            try {
                if ($entity->isAktif() === true) {
                    $message = $this->get('translator')->trans('exception.larangan.hapus.ortuwali');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.ortuwali.terhapus',
                                                array(
                                                    '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error',
                            $this->get('translator')
                                    ->trans('flash.ortuwali.gagal.dihapus',
                                            array(
                                                '%siswa%' => $entity->getSiswa()->getNamaLengkap()
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('parentguard',
                                        array(
                                            'sid' => $sid,
                                        )));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.registration']->setCurrent(true);
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
