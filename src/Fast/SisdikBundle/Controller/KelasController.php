<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Kelas;
use Fast\SisdikBundle\Form\KelasType;
use Fast\SisdikBundle\Form\KelasSearchType;
use Fast\SisdikBundle\Form\KelasDuplicateType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Kelas controller.
 *
 * @Route("/data/class")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class KelasController extends Controller
{
    /**
     * Lists all Kelas entities.
     *
     * @Route("/", name="data_class")
     * @Template()
     */
    public function indexAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new KelasSearchType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.idjenjang', 't2')->leftJoin('t.idtahun', 't3')
                ->where('t.idsekolah = :idsekolah')
                ->orderBy('t3.urutan DESC, t2.urutan ASC, t.urutan', 'ASC')
                ->setParameter('idsekolah', $idsekolah);

        $searchform->bind($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['idtahun'] != '') {
                $querybuilder->andWhere('t.idtahun = :idtahun');
                $querybuilder->setParameter('idtahun', $searchdata['idtahun']);
            }
        }

        $duplicateform = $this->createForm(new KelasDuplicateType($this->container));

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'duplicateform' => $duplicateform->createView()
        );
    }

    /**
     * Finds and displays a Kelas entity.
     *
     * @Route("/{id}/show", name="data_class_show")
     * @Template()
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new Kelas entity.
     *
     * @Route("/new", name="data_class_new")
     * @Template()
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Kelas();
        $form = $this->createForm(new KelasType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Kelas entity.
     *
     * @Route("/create", name="data_class_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:Kelas:new.html.twig")
     */
    public function createAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Kelas();
        $form = $this->createForm(new KelasType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.class.inserted',
                                                array(
                                                        '%class%' => $entity->getNama(),
                                                        '%year%' => $entity->getIdtahun()
                                                                ->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_class_show',
                                                array(
                                                    'id' => $entity->getId()
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.class.year.school');
                throw new DBALException($exception);
            }
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Kelas entity.
     *
     * @Route("/{id}/edit", name="data_class_edit")
     * @Template()
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $editForm = $this->createForm(new KelasType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Kelas entity.
     *
     * @Route("/{id}/update", name="data_class_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Kelas:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Kelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new KelasType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.class.updated',
                                                array(
                                                        '%class%' => $entity->getNama(),
                                                        '%year%' => $entity->getIdtahun()
                                                                ->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('data_class_edit',
                                                array(
                                                        'id' => $id,
                                                        'page' => $this->getRequest()->get('page')
                                                )));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.class.year.school');
                throw new DBALException($exception);
            }
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Kelas entity.
     *
     * @Route("/{id}/delete", name="data_class_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:Kelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity Kelas tak ditemukan.');
            }

            try {
                $em->remove($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.data.class.deleted',
                                                array(
                                                        '%class%' => $entity->getNama(),
                                                        '%year%' => $entity->getIdtahun()
                                                                ->getNama()
                                                )));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($message);
            }
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')->trans('flash.data.class.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('data_class',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    /**
     * Duplicates classes from one academic year to another
     * 
     * @Route("/duplicate", name="data_class_duplicate")
     * @Method("POST")
     */
    public function duplicateClassAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new KelasDuplicateType($this->container));
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $duplicatedata = $form->getData();

            $idtahunSource = $duplicatedata['idtahunSource'];
            $idtahunTarget = $duplicatedata['idtahunTarget'];

            // get all classes from the source academic year
            $entities = $em->getRepository('FastSisdikBundle:Kelas')
                    ->findBy(
                            array(
                                'idtahun' => $idtahunSource->getId()
                            ));

            foreach ($entities as $entity) {
                // remove year code identity from class code
                $kode = substr($entity->getKode(), strlen($entity->getIdtahun()->getKode()));

                $kelas = new Kelas();
                $kelas->setIdjenjang($entity->getIdjenjang());
                $kelas->setIdsekolah($entity->getIdsekolah());
                $kelas->setIdtahun($idtahunTarget);
                $kelas->setKeterangan($entity->getKeterangan());
                $kelas->setKode($kode);
                $kelas->setNama($entity->getNama());
                $kelas->setUrutan($entity->getUrutan());
                try {
                    $em->persist($kelas);
                    $em->flush();
                } catch (DBALException $e) {
                    $exception = $this->get('translator')
                            ->trans('exception.unique.class.year.school');
                    throw new DBALException($exception);
                }
            }

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.data.class.duplicated',
                                            array(
                                                    '%yearfrom%' => $idtahunSource->getNama(),
                                                    '%yearto%' => $idtahunTarget->getNama()
                                            )));
        } else {
            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.data.class.fail.duplicate'));
        }

        return $this->redirect($this->generateUrl('data_class'));
    }

    /**
     * Update class select box
     *
     * @Route("/ajax/updateclass", name="data_class_ajax_updateclass")
     */
    public function ajaxUpdateclassAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $idtahun = $this->getRequest()->query->get('idtahun');
        $idkelas = $this->getRequest()->query->get('idkelas');

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.idjenjang', 't2')->where('t.idsekolah = :idsekolah')
                ->andWhere('t.idtahun = :idtahun')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('idsekolah', $idsekolah)
                ->setParameter('idtahun', $idtahun);
        $results = $querybuilder->getQuery()->getResult();

        $retval = array();
        foreach ($results as $result) {
            $retval[] = array(
                    'optionValue' => $result->getId(), 'optionDisplay' => $result->getNama(),
                    'optionSelected' => $idkelas == $result->getId() ? 'selected' : ''
            );
        }

        $return = json_encode($retval);
        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    /**
     * Update class select box for predefined school
     *
     * @Route("/ajax/updateclass/schooldefined/{idsekolah}", name="data_class_ajax_updateclass_schooldefined")
     */
    public function ajaxUpdateclassSchoolDefinedAction(Request $request, $idsekolah) {
        $em = $this->getDoctrine()->getManager();

        $idtahun = $this->getRequest()->query->get('idtahun');
        $idkelas = $this->getRequest()->query->get('idkelas');

        $querybuilder = $em->createQueryBuilder()->select('t')->from('FastSisdikBundle:Kelas', 't')
                ->leftJoin('t.idjenjang', 't2')->where('t.idsekolah = :idsekolah')
                ->andWhere('t.idtahun = :idtahun')->orderBy('t2.urutan', 'ASC')
                ->addOrderBy('t.urutan')->setParameter('idsekolah', $idsekolah)
                ->setParameter('idtahun', $idtahun);
        $results = $querybuilder->getQuery()->getResult();

        $retval = array();
        foreach ($results as $result) {
            $retval[] = array(
                    'optionValue' => $result->getId(), 'optionDisplay' => $result->getNama(),
                    'optionSelected' => $idkelas == $result->getId() ? 'selected' : ''
            );
        }

        $return = json_encode($retval);
        return new Response($return, 200,
                array(
                    'Content-Type' => 'application/json'
                ));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.data.academic']['links.data.class']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            return $idsekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
