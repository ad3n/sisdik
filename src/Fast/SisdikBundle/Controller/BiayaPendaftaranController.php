<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\BiayaPendaftaranType;
use Fast\SisdikBundle\Form\BiayaSearchFormType;
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
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new BiayaSearchFormType($this->container));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:BiayaPendaftaran', 't')->leftJoin('t.tahun', 't2')
                ->leftJoin('t.gelombang', 't3')->leftJoin('t.jenisbiaya', 't4')
                ->where('t2.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->orderBy('t2.tahun', 'DESC')->addOrderBy('t3.urutan', 'ASC')->addOrderBy('t.urutan', 'ASC')
                ->addOrderBy('t4.nama', 'ASC');

        $searchform->bind($this->getRequest());
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
            'pagination' => $pagination, 'searchform' => $searchform->createView()
        );
    }

    /**
     * Finds and displays a BiayaPendaftaran entity.
     *
     * @Route("/{id}/show", name="fee_registration_show")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity, 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to create a new BiayaPendaftaran entity.
     *
     * @Route("/new", name="fee_registration_new")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function newAction() {
        $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new BiayaPendaftaran entity.
     *
     * @Route("/create", name="fee_registration_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:BiayaPendaftaran:new.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new BiayaPendaftaran();
        $form = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.registration.inserted'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_registration_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/edit", name="fee_registration_edit")
     * @Template()
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        if ($entity->isTerpakai() === true) {
            $this->get('session')->getFlashBag()
                    ->add('info',
                            $this->get('translator')->trans('flash.fee.registration.update.restriction'));
        }

        $editForm = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing BiayaPendaftaran entity.
     *
     * @Route("/{id}/update", name="fee_registration_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:BiayaPendaftaran:edit.html.twig")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new BiayaPendaftaranType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {

                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.registration.updated'));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.fee.registration');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('fee_registration_edit',
                                            array(
                                                'id' => $id, 'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a BiayaPendaftaran entity.
     *
     * @Route("/{id}/delete", name="fee_registration_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_BENDAHARA")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity BiayaPendaftaran tak ditemukan.');
            }

            try {
                if ($entity->isTerpakai() === true) {
                    $message = $this->get('translator')->trans('exception.delete.restrict.registrationfee');
                    throw new \Exception($message);
                }

                $em->remove($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.fee.registration.deleted'));

            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()
                        ->add('info',
                                $this->get('translator')->trans('exception.delete.restrict.registrationfee'));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('fee_registration_show',
                                                array(
                                                    'id' => $id
                                                )));
            }

        } else {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.fee.registration.fail.delete'));
        }

        return $this->redirect($this->generateUrl('fee_registration'));
    }

    /**
     * Finds total payables registration fee info
     *
     * @Route("/totalinfo/{tahun}/{gelombang}/{potongan}/{json}", name="fee_registration_totalinfo", defaults={"potongan"=0, "json"=0})
     */
    public function getTotalFeeInfoAction($tahun, $gelombang, $potongan, $json) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                ->findBy(
                        array(
                            'tahun' => $tahun, 'gelombang' => $gelombang
                        ));

        $total = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $total += $entity->getNominal();
            }
        }

        if ($json == 1) {
            $string = json_encode(array(
                "biaya" => $total - $potongan
            ));
            return new Response($string, 200,
                    array(
                        'Content-Type' => 'application/json'
                    ));
        } else {
            return new Response(number_format($total - $potongan, 0, ',', '.'));
        }
    }

    /**
     * Finds total payment remains registration fee info
     *
     * @Route("/remains/{tahun}/{gelombang}/{paid}/{potongan}", name="fee_registration_remains", defaults={"potongan"=0})
     */
    public function getRemainsPaymentInfoAction($tahun, $gelombang, $paid, $potongan) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                ->findBy(
                        array(
                            'tahun' => $tahun, 'gelombang' => $gelombang
                        ));

        $total = 0;
        foreach ($entities as $entity) {
            if ($entity instanceof BiayaPendaftaran) {
                $total += $entity->getNominal();
            }
        }

        return new Response(number_format(($total - $paid - $potongan), 0, ',', '.'));
    }

    /**
     * Finds info of a fee
     *
     * @Route("/info/{id}/{type}", name="fee_registration_info")
     */
    public function getFeeInfoAction($id, $type = 1) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if ($entity instanceof BiayaPendaftaran) {
            if ($type == 1) {
                $info = $entity->getJenisbiaya()->getNama() . " ("
                        . number_format($entity->getNominal(), 0, ',', '.') . ")";
            } else if ($type == 2) {
                $info = $entity->getJenisbiaya()->getNama();
            } else if ($type == 3) {
                $info = number_format($entity->getNominal(), 0, ',', '.');
            }
        } else {
            $info = $this->get('translator')->trans('label.fee.undefined');
        }

        return new Response($info);
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.fee']['links.fee.registration']->setCurrent(true);
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
