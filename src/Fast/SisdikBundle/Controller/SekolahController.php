<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\SekolahType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * Sekolah controller.
 *
 * @Route("/specsch")
 * @PreAuthorize("hasRole('ROLE_KEPALA_SEKOLAH')")
 */
class SekolahController extends Controller
{
    /**
     * Lists all Sekolah entities.
     *
     * @Route("/", name="settings_specsch")
     * @Template()
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('settings_specsch_show'));
    }

    /**
     * Finds and displays a Sekolah entity.
     *
     * @Route("/show", name="settings_specsch_show")
     * @Template()
     */
    public function showAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Sekolah')->find($idsekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        return array(
            'entity' => $entity
        );
    }

    /**
     * Displays a form to create a new Sekolah entity.
     *
     * @Route("/new", name="settings_specsch_new")
     * @Template()
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new Sekolah();
        $form = $this->createForm(new SekolahType(), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Sekolah entity.
     *
     * @Route("/edit", name="settings_specsch_edit")
     * @Template()
     */
    public function editAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Sekolah')->find($idsekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        $editForm = $this->createForm(new SekolahType(), $entity);

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing Sekolah entity.
     *
     * @Route("/update", name="settings_specsch_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:Sekolah:edit.html.twig")
     */
    public function updateAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:Sekolah')->find($idsekolah->getId());

        if (!$entity) {
            throw $this->createNotFoundException('Entity Sekolah tak ditemukan.');
        }

        $editForm = $this->createForm(new SekolahType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.settings.school.updated',
                                            array(
                                                '%schoolname%' => $entity->getNama()
                                            )));

            return $this->redirect($this->generateUrl('settings_specsch_edit'));
        }

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.setting']['links.school']->setCurrent(true);
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
