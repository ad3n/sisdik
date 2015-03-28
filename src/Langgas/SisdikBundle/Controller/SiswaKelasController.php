<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\Siswa;
use Langgas\SisdikBundle\Entity\SiswaKelas;
use Langgas\SisdikBundle\Entity\Sekolah;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/riwayat-kelas-siswa/{idsiswa}", requirements={"idsiswa"="\d+"})
 * @PreAuthorize("hasAnyRole('ROLE_ADMIN', 'ROLE_KEPALA_SEKOLAH', 'ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class SiswaKelasController extends Controller
{
    /**
     * @Route("/", name="siswa-kelas")
     * @Template()
     */
    public function indexAction($idsiswa)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('view', $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa)) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $querybuilder = $em->createQueryBuilder()
            ->select('siswakelas')
            ->from('LanggasSisdikBundle:SiswaKelas', 'siswakelas')
            ->leftJoin('siswakelas.tahunAkademik', 'tahunAkademik')
            ->leftJoin('siswakelas.kelas', 'kelas')
            ->where('siswakelas.siswa = :siswa')
            ->orderBy('tahunAkademik.urutan', 'DESC')
            ->addOrderBy('tahunAkademik.nama', 'DESC')
            ->addOrderBy('kelas.urutan', 'ASC')
            ->addOrderBy('siswakelas.aktif', 'ASC')
            ->setParameter('siswa', $idsiswa)
        ;

        $results = $querybuilder->getQuery()->getResult();

        return [
            'results' => $results,
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa),
        ];
    }

    /**
     * @Route("/new", name="siswa-kelas_new")
     * @Template()
     */
    public function newAction($idsiswa)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('create', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new SiswaKelas();
        $entity->setSiswa($siswa);
        $form = $this->createForm('sisdik_siswakelas', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'siswa' => $siswa,
        ];
    }

    /**
     * @Route("/create", name="siswa-kelas_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaKelas:new.html.twig")
     */
    public function createAction($idsiswa)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('create', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = new SiswaKelas();
        $form = $this->createForm('sisdik_siswakelas', $entity);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // hanya boleh ada satu status aktif di tahun akademik yang sama
            $aktif = $form->get('aktif')->getData();
            if ($aktif == 1) {
                $kelasAktif = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
                    ->findOneBy([
                        'siswa' => $siswa,
                        'tahunAkademik' => $form->get('tahunAkademik')->getData(),
                        'aktif' => $aktif,
                    ])
                ;
                if ($kelasAktif) {
                    $exception = $this->get('translator')->trans('exception.unique.studentclass.active');
                    throw new \Exception($exception);
                }
            }

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.studentclass.inserted', [
                        '%student%' => $siswa->getNamaLengkap(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('siswa-kelas', [
                    'idsiswa' => $idsiswa,
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.studentclass');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity, 'form' => $form->createView(),
            'siswa' => $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa),
        ];
    }

    /**
     * @Route("/{id}/edit", name="siswa-kelas_edit")
     * @Template()
     */
    public function editAction($idsiswa, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:SiswaKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_siswakelas', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'siswa' => $siswa,
        ];
    }

    /**
     * @Route("/{id}/update", name="siswa-kelas_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:SiswaKelas:edit.html.twig")
     */
    public function updateAction($idsiswa, $id)
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('edit', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $entity = $em->getRepository('LanggasSisdikBundle:SiswaKelas')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_siswakelas', $entity);
        $editForm->submit($this->getRequest());

        if ($editForm->isValid()) {
            // hanya boleh ada satu status aktif di tahun akademik yang sama
            $aktif = $editForm->get('aktif')->getData();
            if ($aktif == 1) {
                $kelasAktif = $em->getRepository('LanggasSisdikBundle:SiswaKelas')
                    ->findOneBy([
                        'siswa' => $siswa,
                        'tahunAkademik' => $editForm->get('tahunAkademik')->getData(),
                        'aktif' => $aktif,
                    ])
                ;
                if (is_object($kelasAktif) && $kelasAktif instanceof SiswaKelas && ($kelasAktif->getId() != $entity->getId())) {
                    $exception = $this->get('translator')->trans('exception.unique.studentclass.active');
                    throw new \Exception($exception);
                }
            }

            try {
                $em->persist($entity);
                $em->flush();

                $this
                    ->get('session')
                    ->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.data.studentclass.updated', [
                        '%student%' => $siswa->getNamaLengkap(),
                    ]))
                ;

                return $this->redirect($this->generateUrl('siswa-kelas', [
                    'idsiswa' => $idsiswa,
                ]));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.unique.studentclass');
                throw new DBALException($exception);
            }
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'siswa' => $siswa,
        ];
    }

    /**
     * @Route("/{id}/delete", name="siswa-kelas_delete")
     * @Method("POST")
     */
    public function deleteAction($idsiswa, $id)
    {
        $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $siswa = $em->getRepository('LanggasSisdikBundle:Siswa')->find($idsiswa);
        if (!(is_object($siswa) && $siswa instanceof Siswa)) {
            throw $this->createNotFoundException('Entity Siswa tak ditemukan.');
        }

        if ($this->get('security.authorization_checker')->isGranted('delete', $siswa) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $form = $this->createDeleteForm($id);
        $form->submit($this->getRequest());

        if ($form->isValid()) {
            $entity = $em->getRepository('LanggasSisdikBundle:SiswaKelas')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity SiswaKelas tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.studentclass.deleted', [
                    '%student%' => $siswa->getNamaLengkap(),
                    '%class%' => $entity->getKelas()->getNama(),
                    '%year%' => $entity->getTahunAkademik()->getNama(),
                ]))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.data.studentclass.fail.delete', [
                    '%student%' => $siswa->getNamaLengkap(),
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('siswa-kelas', [
            'idsiswa' => $idsiswa,
        ]));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id,
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.academic', [], 'navigations')][$translator->trans('links.siswa', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
