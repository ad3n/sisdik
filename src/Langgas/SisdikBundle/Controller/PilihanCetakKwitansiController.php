<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Langgas\SisdikBundle\Entity\PilihanCetakKwitansi;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/pilihan-cetak-kwitansi")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KASIR')")
 */
class PilihanCetakKwitansiController extends Controller
{
    /**
     * @Route("/", name="printreceiptsoption")
     * @Template()
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $pilihanCetak = $em->getRepository('LanggasSisdikBundle:PilihanCetakKwitansi')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        if (!($pilihanCetak instanceof PilihanCetakKwitansi)) {
            return $this->redirect($this->generateUrl('printreceiptsoption_new'));
        } else {
            return $this->redirect($this->generateUrl('printreceiptsoption_edit', [
                'id' => $pilihanCetak->getId(),
            ]));
        }
    }

    /**
     * @Route("/new", name="printreceiptsoption_new")
     * @Template()
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $entity = new PilihanCetakKwitansi();
        $form = $this->createForm('sisdik_pilihancetakkwitansi', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/create", name="printreceiptsoption_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanCetakKwitansi:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new PilihanCetakKwitansi();
        $form = $this->createForm('sisdik_pilihancetakkwitansi', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.printreceiptsoption.saved'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.printreceiptsoption');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('printreceiptsoption'));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}/edit", name="printreceiptsoption_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanCetakKwitansi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanCetakKwitansi tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_pilihancetakkwitansi', $entity);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @Route("/{id}/update", name="printreceiptsoption_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PilihanCetakKwitansi:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PilihanCetakKwitansi')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PilihanCetakKwitansi tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_pilihancetakkwitansi', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.printreceiptsoption.saved'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.printreceiptsoption');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('printreceiptsoption_edit', [
                'id' => $id,
            ]));
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
        $menu[$translator->trans('headings.payments', [], 'navigations')][$translator->trans('links.printreceiptsoption', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
