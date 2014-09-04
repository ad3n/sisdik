<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\VendorSekolah;
use Langgas\SisdikBundle\Entity\Sekolah;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/penyedia-jasa")
 */
class VendorSekolahController extends Controller
{
    /**
     * @Route("/", name="vendor_sekolah")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('vendorSekolah')
            ->from('LanggasSisdikBundle:VendorSekolah', 'vendorSekolah')
            ->where('vendorSekolah.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->setMaxResults(1)
        ;
        $results = $querybuilder->getQuery()->getResult();

        $entity = false;
        foreach ($results as $result) {
            if (is_object($result) && $result instanceof VendorSekolah) {
                $entity = $result;
            }
        }

        if (!$entity) {
            return $this->redirect($this->generateUrl('vendor_sekolah_new'));
        } else {
            return $this->redirect($this->generateUrl('vendor_sekolah_edit', [
                'id' => $entity->getId(),
            ]));
        }
    }

    /**
     * @Route("/", name="vendor_sekolah_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:VendorSekolah:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new VendorSekolah;
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.vendor.sekolah.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.vendor.sekolah');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('vendor_sekolah'));
        }

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * @param  VendorSekolah                $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm(VendorSekolah $entity)
    {
        $form = $this->createForm('sisdik_vendorsekolah', $entity, [
            'action' => $this->generateUrl('vendor_sekolah_create'),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * @Route("/new", name="vendor_sekolah_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new VendorSekolah;
        $form   = $this->createCreateForm($entity);

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="vendor_sekolah_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity penyedia jasa sms.');
        }

        $editForm = $this->createEditForm($entity);

        return [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ];
    }

    /**
     * @param  VendorSekolah                $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(VendorSekolah $entity)
    {
        $form = $this->createForm('sisdik_vendorsekolah', $entity, [
            'action' => $this->generateUrl('vendor_sekolah_update', [
                'id' => $entity->getId(),
            ]),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/{id}", name="vendor_sekolah_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:VendorSekolah:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:VendorSekolah')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Tak bisa menemukan entity penyedia jasa sms.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.vendor.sekolah.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.vendor.sekolah');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('vendor_sekolah_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ];
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.penyedia.jasa.sms', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
