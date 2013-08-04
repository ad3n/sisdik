<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\User;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Entity\Siswa;
use Fast\SisdikBundle\Form\SiswaSearchType;
use Fast\SisdikBundle\Form\SiswaImportType;
use Fast\SisdikBundle\Form\SiswaExportType;
use Fast\SisdikBundle\Util\EasyCSV\Reader;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Siswa controller.
 *
 * @Route("/penempatan-siswa-kelas")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class PenempatanSiswaKelasController extends Controller
{
    /**
     * Lists all Siswa entities.
     *
     * @Route("/", name="penempatan-siswa-kelas")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new SiswaSearchType($this->container));
        $searchkey = '';

        $querybuilder = $em->createQueryBuilder()->select('siswa')->from('FastSisdikBundle:Siswa', 'siswa')
                ->leftJoin('siswa.tahun', 'tahun')->leftJoin('siswa.orangtuaWali', 'orangtua')
                ->andWhere('orangtua.aktif = :ortuaktif')->setParameter('ortuaktif', true)
                ->andWhere('siswa.calonSiswa = :calon')->setParameter('calon', false)
                ->andWhere('siswa.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->orderBy('tahun.tahun', 'DESC')->addOrderBy('siswa.namaLengkap', 'ASC');

        $searchform->submit($this->getRequest());
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahun'] != '') {
                $querybuilder->andWhere('tahun.id = :tahun');
                $querybuilder->setParameter('tahun', $searchdata['tahun']->getId());
            }

            if ($searchdata['searchkey'] != '') {
                $searchkey = $searchdata['searchkey'];

                $querybuilder
                        ->andWhere(
                                "siswa.namaLengkap LIKE :namalengkap OR siswa.nomorInduk LIKE :nomorinduk"
                                        . ' OR orangtua.nama LIKE :namaortu '
                                        . ' OR orangtua.ponsel LIKE :ponselortu ');
                $querybuilder->setParameter('namalengkap', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('nomorinduk', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('namaortu', "%{$searchdata['searchkey']}%");
                $querybuilder->setParameter('ponselortu', "%{$searchdata['searchkey']}%");
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $dlform = $this->createForm(new SiswaExportType($this->container));

        return array(
            'pagination' => $pagination, 'searchform' => $searchform->createView(), 'searchkey' => $searchkey,
        );
    }

    /**
     * Displays a form to import Siswa entities.
     *
     * @Route("/import", name="penempatan-siswa-kelas_import")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function importStudentAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $form = $this->createForm(new SiswaImportType($this->container));

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->submit($this->getRequest());

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $file = $form['file']->getData();
                $delimiter = $form['delimiter']->getData();

                $tahun = $form['tahun']->getData();
                $gelombang = $form['gelombang']->getData();

                $reader = new Reader($file->getPathName(), "r+", $delimiter);

                while ($row = $reader->getRow()) {
                    $this->importStudent($row, $reader->getHeaders(), $sekolah, $tahun, $gelombang);
                }

                try {
                    $em->flush();
                } catch (DBALException $e) {
                    $message = $this->get('translator')->trans('exception.studentid.unique');
                    throw new DBALException($message);
                } catch (Exception $e) {
                    $message = $this->get('translator')->trans('exception.import.error');
                    throw new \Exception($message);
                }

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.data.student.imported',
                                                array(
                                                        '%count%' => $this->importStudentCount,
                                                        '%year%' => $tahun->getTahun(),
                                                        '%admission%' => $gelombang->getNama()
                                                )));

                return $this->redirect($this->generateUrl('siswa'));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.penempatan.siswa.kelas']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.useadmin.or.headmaster'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
