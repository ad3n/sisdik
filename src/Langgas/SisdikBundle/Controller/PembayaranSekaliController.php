<?php

namespace Langgas\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Langgas\SisdikBundle\Entity\PembayaranSekali;
use Langgas\SisdikBundle\Form\PembayaranSekaliType;
use Langgas\SisdikBundle\Entity\TransaksiPembayaranSekali;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * PembayaranSekali controller.
 *
 * @Route("/payment/oncefee/{cid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA')")
 */
class PembayaranSekaliController extends Controller
{
    /**
     * Menentukan mode input atau edit untuk pembayaran sekali seorang siswa
     *
     * @Route("/", name="payment_oncefee")
     * @Template()
     */
    public function indexAction($cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('LanggasSisdikBundle:PembayaranSekali', 't')->where('t.siswa = :siswa')
                ->setParameter('siswa', $cid)->setMaxResults(1);
        $results = $querybuilder->getQuery()->getResult();
        $entity = false;
        foreach ($results as $result) {
            if (is_object($result) && $result instanceof PembayaranSekali) {
                $entity = $result;
            } else {
                $entity = false;
            }
        }

        if (!$entity) {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_oncefee_new',
                                            array(
                                                'cid' => $cid, 'page' => $this->getRequest()->get('page')
                                            )));
        } else {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $entity->getId(),
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }
    }

    /**
     * Displays a form to create a new PembayaranSekali entity.
     *
     * @Route("/new", name="payment_oncefee_new")
     * @Template()
     */
    public function newAction($cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new PembayaranSekali();
        $transaksiPembayaranSekali = new TransaksiPembayaranSekali();
        $entity->getTransaksiPembayaranSekali()->add($transaksiPembayaranSekali);

        $form = $this->createForm(new PembayaranSekaliType($this->container, $cid), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($cid),
        );
    }

    /**
     * Creates a new PembayaranSekali entity.
     *
     * @Route("/create", name="payment_oncefee_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranSekali:new.html.twig")
     */
    public function createAction(Request $request, $cid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($cid);

        $entity = new PembayaranSekali();
        $form = $this->createForm(new PembayaranSekaliType($this->container, $cid), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $entity->setSiswa($siswa);

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.oncefee.inserted',
                                                array(
                                                    '%name%' => $siswa->getNamaLengkap()
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant.oncefee');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $entity->getId(),
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(), 'siswa' => $siswa,
        );
    }

    /**
     * Displays a form to edit existing PembayaranSekali entities attached to an applicant.
     *
     * @Route("/{id}/edit", name="payment_oncefee_edit")
     * @Template()
     */
    public function editAction($cid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranSekali')->find($id);
        $transaksiPembayaranSekali = new TransaksiPembayaranSekali();
        $entity->getTransaksiPembayaranSekali()->add($transaksiPembayaranSekali);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PembayaranSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new PembayaranSekaliType($this->container, $cid), $entity);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($cid),
        );
    }

    /**
     * Edits an existing PembayaranSekali entity.
     *
     * @Route("/{id}/update", name="payment_oncefee_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:PembayaranSekali:edit.html.twig")
     */
    public function updateAction(Request $request, $cid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:PembayaranSekali')->find($id);

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($cid);

        if (!$entity) {
            throw $this->createNotFoundException('Entity PembayaranSekali tak ditemukan.');
        }

        $editForm = $this->createForm(new PembayaranSekaliType($this->container, $cid), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {

            try {
                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.applicant.oncefee.updated',
                                                array(
                                                    '%name%' => $siswa->getNamaLengkap(),
                                                )));

            } catch (DBALException $e) {
                $message = $this->get('translator')->trans('exception.unique.applicant.oncefee');
                throw new DBALException($message);
            }

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_oncefee_edit',
                                            array(
                                                    'cid' => $cid, 'id' => $id,
                                                    'page' => $this->getRequest()->get('page')
                                            )));
        }

        return array(
            'entity' => $entity, 'edit_form' => $editForm->createView(), 'siswa' => $siswa,
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.payments', array(), 'navigations')][$this->get('translator')->trans('links.applicant.payment', array(), 'navigations')]->setCurrent(true);
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