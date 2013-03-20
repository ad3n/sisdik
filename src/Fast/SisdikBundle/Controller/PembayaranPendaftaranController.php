<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\PembayaranPendaftaran;
use Fast\SisdikBundle\Entity\TransaksiPembayaranPendaftaran;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\BiayaPendaftaran;
use Fast\SisdikBundle\Form\PembayaranPendaftaranType;
use Fast\SisdikBundle\Form\PembayaranPendaftaranCicilanType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * PembayaranPendaftaran controller.
 *
 * @Route("/payment/registrationfee/{sid}")
 * @PreAuthorize("hasAnyRole('ROLE_BENDAHARA', 'ROLE_KETUA_PANITIA_PSB')")
 */
class PembayaranPendaftaranController extends Controller
{
    /**
     * Lists all PembayaranPendaftaran entities.
     *
     * @Route("/", name="payment_registrationfee")
     * @Template()
     */
    public function indexAction($sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $jumlahItemBiaya = count(
                $em->getRepository('FastSisdikBundle:BiayaPendaftaran')
                        ->findBy(
                                array(
                                        'tahunmasuk' => $siswa->getTahunmasuk(),
                                        'gelombang' => $siswa->getGelombang(),
                                )));

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $totalBiaya = array();
        $editLink = array();
        $biayaTerbayar = array();
        $jumlahItemBiayaTerbayar = 0;
        foreach ($entities as $pembayaran) {
            if (is_object($pembayaran) && $pembayaran instanceof PembayaranPendaftaran) {
                $nominalBiaya = 0;
                $jumlahItemBiayaTerbayar += count($pembayaran->getDaftarBiayaPendaftaran());
                foreach ($pembayaran->getDaftarBiayaPendaftaran() as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    $nominalBiaya += $biayaPendaftaran->getNominal();
                }
                $totalBiaya[] = $nominalBiaya;

                if ($pembayaran->getTotalNominalTransaksiPembayaranPendaftaran() == $nominalBiaya
                        && $nominalBiaya != 0) {
                    $editLink[] = false;
                } else {
                    $editLink[] = true;
                }

                unset($tmp);
                $tmp = array_merge($pembayaran->getDaftarBiayaPendaftaran(), $biayaTerbayar);

                unset($biayaTerbayar);
                $biayaTerbayar = $tmp;
            }
        }

        if ($jumlahItemBiaya == $jumlahItemBiayaTerbayar && $jumlahItemBiayaTerbayar > 0) {
            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahItemBiaya' => $jumlahItemBiaya,
                    'jumlahItemBiayaTerbayar' => $jumlahItemBiayaTerbayar, 'totalBiaya' => $totalBiaya,
                    'editLink' => $editLink,
            );
        } else {
            $entity = new PembayaranPendaftaran();
            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);

            $form = $this
                    ->createForm(new PembayaranPendaftaranType($this->container, $sid, $biayaTerbayar),
                            $entity);

            return array(
                    'entities' => $entities, 'siswa' => $siswa, 'jumlahItemBiaya' => $jumlahItemBiaya,
                    'jumlahItemBiayaTerbayar' => $jumlahItemBiayaTerbayar, 'form' => $form->createView(),
                    'totalBiaya' => $totalBiaya, 'editLink' => $editLink,
            );
        }
    }

    /**
     * Finds a name of a fee
     *
     * @Route("/{id}/info", name="payment_registrationfee_getfeeinfo")
     */
    public function getFeeInfoAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($id);

        if ($entity instanceof BiayaPendaftaran) {
            $info = $entity->getJenisbiaya()->getNama() . " ("
                    . number_format($entity->getNominal(), 0, ',', '.') . ")";
        } else {
            $info = $this->get('translator')->trans('label.fee.undefined');
        }

        return new Response($info);
    }

    /**
     * Creates a new PembayaranPendaftaran entity.
     *
     * @Route("/create", name="payment_registrationfee_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:PembayaranPendaftaran:index.html.twig")
     */
    public function createAction(Request $request, $sid) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entities = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')
                ->findBy(array(
                    'siswa' => $siswa
                ));

        $biayaTerbayar = array();
        foreach ($entities as $pembayaran) {
            if (is_object($pembayaran) && $pembayaran instanceof PembayaranPendaftaran) {
                $nominalBiaya = 0;
                foreach ($pembayaran->getDaftarBiayaPendaftaran() as $biaya) {
                    $biayaPendaftaran = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($biaya);
                    $nominalBiaya += $biayaPendaftaran->getNominal();
                }

                unset($tmp);
                $tmp = array_merge($pembayaran->getDaftarBiayaPendaftaran(), $biayaTerbayar);

                unset($biayaTerbayar);
                $biayaTerbayar = $tmp;
            }
        }

        $entity = new PembayaranPendaftaran();
        $form = $this
                ->createForm(new PembayaranPendaftaranType($this->container, $sid, $biayaTerbayar), $entity);
        $form->bind($request);

        if ($form->isValid()) {

            $entity->setSiswa($siswa);

            $em->persist($entity);
            $em->flush();

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_registrationfee',
                                            array(
                                                'sid' => $sid,
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a PembayaranPendaftaran entity.
     *
     * @Route("/{id}/show", name="payment_registrationfee_show")
     * @Template()
     */
    public function showAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksi = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        $transaksiPembayaran = $em->getRepository('FastSisdikBundle:TransaksiPembayaranPendaftaran')
                ->findBy(array(
                    'pembayaranPendaftaran' => $id
                ));

        if (!$entity) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        return array(
                'siswa' => $siswa, 'entity' => $entity, 'totalNominalTransaksi' => $totalNominalTransaksi,
                'transaksiPembayaran' => $transaksiPembayaran,
        );
    }

    /**
     * Mengatur cicilan pembayaran biaya pendaftaran
     *
     * @Route("/{id}/edit", name="payment_registrationfee_edit")
     * @Template()
     */
    public function editAction($sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($daftarBiayaPendaftaran[0]);
        $nominalBiaya = $biaya->getNominal();

        if ($totalNominalTransaksiSebelumnya == $nominalBiaya && $totalNominalTransaksiSebelumnya > 0) {
            return array(
                    'siswa' => $siswa, 'entity' => $entity,
                    'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                    'nominalBiaya' => $nominalBiaya,
            );
        } else {
            $transaksiPembayaranPendaftaran = new TransaksiPembayaranPendaftaran();
            $entity->getTransaksiPembayaranPendaftaran()->add($transaksiPembayaranPendaftaran);

            $editForm = $this
                    ->createForm(
                            new PembayaranPendaftaranCicilanType($this->container, $sid,
                                    $daftarBiayaPendaftaran[0]), $entity);

            return array(
                    'siswa' => $siswa, 'entity' => $entity,
                    'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                    'nominalBiaya' => $nominalBiaya, 'edit_form' => $editForm->createView(),
            );
        }
    }

    /**
     * Edits an existing PembayaranPendaftaran entity.
     *
     * @Route("/{id}/update", name="payment_registrationfee_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:PembayaranPendaftaran:edit.html.twig")
     */
    public function updateAction(Request $request, $sid, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('FastSisdikBundle:Siswa')->find($sid);

        $entity = $em->getRepository('FastSisdikBundle:PembayaranPendaftaran')->find($id);
        $daftarBiayaPendaftaran = $entity->getDaftarBiayaPendaftaran();
        $totalNominalTransaksiSebelumnya = $entity->getTotalNominalTransaksiPembayaranPendaftaran();

        if (count($daftarBiayaPendaftaran) != 1) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registrationfee.gt.one'));
        }

        $biaya = $em->getRepository('FastSisdikBundle:BiayaPendaftaran')->find($daftarBiayaPendaftaran[0]);
        $nominalBiaya = $biaya->getNominal();

        if (!$entity) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }

        $editForm = $this
                ->createForm(
                        new PembayaranPendaftaranCicilanType($this->container, $sid,
                                $daftarBiayaPendaftaran[0]), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {

            $entity
                    ->setDaftarBiayaPendaftaran(
                            array(
                                $entity->getDaftarBiayaPendaftaran()
                            ));

            $em->persist($entity);
            $em->flush();
            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.fee.registration.mortgage.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('payment_registrationfee',
                                            array(
                                                'sid' => $sid,
                                            )));
        }

        return array(
                'siswa' => $siswa, 'entity' => $entity,
                'totalNominalTransaksiSebelumnya' => $totalNominalTransaksiSebelumnya,
                'nominalBiaya' => $nominalBiaya, 'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Prints registration fee payment receipt
     *
     * @Route("/{pid}/printreceipt/{id}", name="payment_registrationfee_print_receipt", defaults={"id"=0})
     */
    public function printReceiptAction(Request $request, $sid, $pid, $id) {
        if (!$entity) {
            throw $this->createNotFoundException('Entity PembayaranPendaftaran tak ditemukan.');
        }
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
