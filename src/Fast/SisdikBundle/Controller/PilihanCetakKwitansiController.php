<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PilihanCetakKwitansi;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\PilihanCetakKwitansiType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * PilihanCetakKwitansi controller.
 *
 * @Route("/printreceiptsoption")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KETUA_PANITIA_PSB')")
 */
class PilihanCetakKwitansiController extends Controller
{
    /**
     * Decide whether display new or edit form
     *
     * @Route("/", name="printreceiptsoption")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:PilihanCetakKwitansi', 't')->where('t.sekolah = :sekolah')
                ->setParameter('sekolah', $sekolah->getId())->setMaxResults(1);
        $results = $querybuilder->getQuery()->getResult();
        $entity = false;
        foreach ($results as $result) {
            if (is_object($result) && $result instanceof PilihanCetakKwitansi) {
                $entity = $result;
            } else {
                $entity = false;
            }
        }

        if (!$entity) {
            return $this->redirect($this->generateUrl('printreceiptsoption_new'));
        } else {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('printreceiptsoption_edit',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }
    }

    /**
     * Displays a form to create a new PilihanCetakKwitansi entity.
     *
     * @Route("/new", name="printreceiptsoption_new")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PilihanCetakKwitansi();
        $form = $this->createForm(new PilihanCetakKwitansiType($this->container), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Creates a new PilihanCetakKwitansi entity.
     *
     * @Route("/create", name="printreceiptsoption_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanCetakKwitansi:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new PilihanCetakKwitansi();
        $form = $this->createForm(new PilihanCetakKwitansiType($this->container), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.printreceiptsoption.saved'));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.printreceiptsoption');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('printreceiptsoption'));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing PilihanCetakKwitansi entity.
     *
     * @Route("/{id}/edit", name="printreceiptsoption_edit")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanCetakKwitansi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanCetakKwitansi tak ditemukan.');
        }

        $editForm = $this->createForm(new PilihanCetakKwitansiType($this->container), $entity);

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Edits an existing PilihanCetakKwitansi entity.
     *
     * @Route("/{id}/update", name="printreceiptsoption_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PilihanCetakKwitansi:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:PilihanCetakKwitansi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanCetakKwitansi tak ditemukan.');
        }

        $editForm = $this->createForm(new PilihanCetakKwitansiType($this->container), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success', $this->get('translator')->trans('flash.printreceiptsoption.saved'));
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.printreceiptsoption');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('printreceiptsoption_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(),
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.printreceiptsoption']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
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
