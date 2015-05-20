<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/kodifikasi-transaksi")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA')")
 */
class KodifikasiTransaksiController extends Controller
{
    /**
     * @Route("/", name="kodifikasi_transaksi")
     * @Template("LanggasSisdikBundle:Sekolah:kodifikasi.transaksi.html.twig")
     */
    public function indexAction()
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $editForm = $this->createEditForm($sekolah);

        return [
            'entity' => $sekolah,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * @param  Sekolah                      $entity
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(Sekolah $entity)
    {
        $form = $this->createForm('sisdik_kodifikasitransaksi', $entity, [
            'action' => $this->generateUrl('kodifikasi_transaksi_update'),
            'method' => 'PUT',
        ]);

        return $form;
    }

    /**
     * @Route("/update", name="kodifikasi_transaksi_update")
     * @Method("PUT")
     * @Template("LanggasSisdikBundle:Sekolah:kodifikasi.transaksi.html.twig")
     */
    public function updateAction(Request $request)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createEditForm($sekolah);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            try {
                $em->persist($sekolah);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.kodifikasi.transaksi.tersimpan'))
                ;
            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.gagal.menyimpan.kodifikasi.transaksi');
                throw new DBALException($message);
            }

            return $this->redirect($this->generateUrl('kodifikasi_transaksi'));
        }

        return [
            'entity' => $sekolah,
            'edit_form' => $editForm->createView(),
        ];
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.fee', [], 'navigations')][$translator->trans('links.kodifikasi.transaksi', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
