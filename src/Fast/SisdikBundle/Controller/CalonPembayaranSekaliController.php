<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

use Fast\SisdikBundle\Entity\CalonTransaksiPembayaranSekali;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\CalonPembayaranSekali;
use Fast\SisdikBundle\Form\CalonPembayaranSekaliType;
use Fast\SisdikBundle\Entity\CalonSiswa;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * CalonPembayaranSekali controller.
 *
 * @Route("/applicant-payment/oncefee/{cid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KETUA_PANITIA_PSB')")
 */
class CalonPembayaranSekaliController extends Controller
{
    /**
     * Menentukan mode input atau edit untuk pembayaran sekali seorang calon siswa
     *
     * @Route("/", name="applicant_payment_oncefee")
     * @Template()
     */
    public function indexAction($cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:CalonPembayaranSekali', 't')->where('t.calonSiswa = :calonsiswa')
                ->setParameter('calonsiswa', $cid)->setMaxResults(1);
        $results = $querybuilder->getQuery()->getResult();
        $entity = false;
        foreach ($results as $result) {
            if (is_object($result) && $result instanceof CalonPembayaranSekali) {
                $entity = $result;
            } else {
                $entity = false;
            }
        }

        if (!$entity) {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_payment_oncefee_new',
                                            array(
                                                'cid' => $cid, 'page' => $this->getRequest()->get('page')
                                            )));
        } else {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $entity->getId(),
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }
    }

    /**
     * Displays a form to create a new CalonPembayaranSekali entity.
     *
     * @Route("/new", name="applicant_payment_oncefee_new")
     * @Template()
     */
    public function newAction($cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new CalonPembayaranSekali();
        $calonTransaksiPembayaranSekali = new CalonTransaksiPembayaranSekali();
        $entity->getCalonTransaksiPembayaranSekali()->add($calonTransaksiPembayaranSekali);

        $form = $this->createForm(new CalonPembayaranSekaliType($this->container, $cid), $entity);

        $formatter = new \NumberFormatter($this->getRequest()->getLocale(), \NumberFormatter::CURRENCY);
        $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'calonSiswa' => $em->getRepository('FastSisdikBundle:CalonSiswa')->find($cid),
                'currencySymbol' => $currencySymbol,
        );
    }

    /**
     * Creates a new CalonPembayaranSekali entity.
     *
     * @Route("/create", name="applicant_payment_oncefee_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:CalonPembayaranSekali:new.html.twig")
     */
    public function createAction(Request $request, $cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $calonSiswa = $em->getRepository('FastSisdikBundle:CalonSiswa')->find($cid);

        $entity = new CalonPembayaranSekali();
        $form = $this->createForm(new CalonPembayaranSekaliType($this->container, $cid), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $entity->setCalonSiswa($calonSiswa);

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.oncefee.inserted',
                                                array(
                                                    '%name%' => $calonSiswa->getNamaLengkap()
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant.oncefee');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $entity->getId(),
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }

        $formatter = new \NumberFormatter($this->getRequest()->getLocale(), \NumberFormatter::CURRENCY);
        $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return array(
                'entity' => $entity, 'form' => $form->createView(), 'calonSiswa' => $calonSiswa,
                'currencySymbol' => $currencySymbol,
        );
    }

    /**
     * Displays a form to edit existing CalonPembayaranSekali entities attached to an applicant.
     *
     * @Route("/{id}/edit", name="applicant_payment_oncefee_edit")
     * @Template()
     */
    public function editAction($cid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:CalonPembayaranSekali')->find($id);
        $calonTransaksiPembayaranSekali = new CalonTransaksiPembayaranSekali();
        $entity->getCalonTransaksiPembayaranSekali()->add($calonTransaksiPembayaranSekali);

        if (!$entity) {
            throw $this->createNotFoundException('Entity CalonPembayaranSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new CalonPembayaranSekaliType($this->container, $cid), $entity);

        $formatter = new \NumberFormatter($this->getRequest()->getLocale(), \NumberFormatter::CURRENCY);
        $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'calonSiswa' => $em->getRepository('FastSisdikBundle:CalonSiswa')->find($cid),
                'currencySymbol' => $currencySymbol,
        );
    }

    /**
     * Edits an existing CalonPembayaranSekali entity.
     *
     * @Route("/{id}/update", name="applicant_payment_oncefee_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:CalonPembayaranSekali:edit.html.twig")
     */
    public function updateAction(Request $request, $cid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:CalonPembayaranSekali')->find($id);

        $calonSiswa = $em->getRepository('FastSisdikBundle:CalonSiswa')->find($cid);

        if (!$entity) {
            throw $this->createNotFoundException('Entity CalonPembayaranSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new CalonPembayaranSekaliType($this->container, $cid), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')
                        ->setFlash('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.oncefee.updated',
                                                array(
                                                    '%name%' => $calonSiswa->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant.oncefee');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('applicant_payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $id,
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }

        $formatter = new \NumberFormatter($this->getRequest()->getLocale(), \NumberFormatter::CURRENCY);
        $currencySymbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(), 'calonSiswa' => $calonSiswa,
                'currencySymbol' => $currencySymbol,
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.payments']['links.applicant.payment']->setCurrent(true);
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
