<?php

namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/info-sekolah")
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH')")
 */
class SekolahController extends Controller
{
    /**
     * @Route("/", name="settings_specsch")
     * @Template("LanggasSisdikBundle:Sekolah:show.html.twig")
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($sekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        return [
            'entity' => $entity,
        ];
    }

    /**
     * @Route("/edit", name="settings_specsch_edit")
     * @Template()
     */
    public function editAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($sekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_sekolah', $entity);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/update", name="settings_specsch_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:Sekolah:edit.html.twig")
     */
    public function updateAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:Sekolah')->find($sekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_sekolah', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            if ($editForm['fileUpload']->getData() !== null) {
                $entity->setLogo("temp_".uniqid(mt_rand(), true));
            }

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.settings.school.updated', [
                    '%schoolname%' => $entity->getNama(),
                ]))
            ;

            return $this->redirect($this->generateUrl('settings_specsch_edit'));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.setting', [], 'navigations')][$translator->trans('links.school', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
