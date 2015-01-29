<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController as Controller;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\JadwalKepulangan;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\TokenSekolah;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Kelas;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/")
 */
class JadwalKepulanganController extends Controller
{
    /**
     * @Route("/jadwal-kepulangan", name="jadwal_kepulangan")
     * @Method("GET")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('jadwal_kepulangan_list'));
    }

    /**
     * @Route("/jadwal-kepulangan/daftar/{perulangan}", name="jadwal_kepulangan_list")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:JadwalKepulangan:index.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function listAction($perulangan = 'harian')
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carijadwal', ['perulangan' => $perulangan]);

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwalKepulangan')
            ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwalKepulangan')
            ->where('jadwalKepulangan.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->addOrderBy('jadwalKepulangan.permulaan', 'DESC')
            ->addOrderBy('jadwalKepulangan.statusKepulangan', 'ASC')
            ->addOrderBy('jadwalKepulangan.paramstatusDariJam', 'ASC')
        ;

        $searchform->submit($this->getRequest());

        $data = [];
        $displayresult = false;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder->andWhere('jadwalKepulangan.tahunAkademik = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $searchdata['tahunAkademik']);
                $data['tahunAkademik'] = $searchdata['tahunAkademik'];
            }
            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder->andWhere('jadwalKepulangan.kelas = :kelas');
                $querybuilder->setParameter('kelas', $searchdata['kelas']);
                $data['kelas'] = $searchdata['kelas'];
            }
            if ($searchdata['perulangan'] != '') {
                $querybuilder->andWhere("(jadwalKepulangan.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $searchdata['perulangan']);
                $data['perulangan'] = $searchdata['perulangan'];

                $perulangan = $searchdata['perulangan'];
                $displayresult = true;
            }

            if ($searchdata['perulangan'] == 'b-mingguan' && array_key_exists('mingguanHariKe', $searchdata)) {
                $querybuilder->andWhere("(jadwalKepulangan.mingguanHariKe = :mingguanHariKe)");
                if ($searchdata['mingguanHariKe'] === null) {
                    $searchdata['mingguanHariKe'] = 1;
                }
                $querybuilder->setParameter('mingguanHariKe', $searchdata['mingguanHariKe']);
                $data['mingguanHariKe'] = $searchdata['mingguanHariKe'];

                $displayresult = true;
            }

            if ($searchdata['perulangan'] == 'c-bulanan' && array_key_exists('bulananHariKe', $searchdata)) {
                $querybuilder->andWhere("(jadwalKepulangan.bulananHariKe = :bulananHariKe)");
                if ($searchdata['bulananHariKe'] === null) {
                    $searchdata['bulananHariKe'] = 1;
                }
                $querybuilder->setParameter('bulananHariKe', $searchdata['bulananHariKe']);
                $data['bulananHariKe'] = $searchdata['bulananHariKe'];

                $displayresult = true;
            }
        }

        $options = [];
        if (count($data) > 0) {
            $options = [
                'sekolahSrc' => $sekolah->getId(),
                'tahunAkademikSrc' => array_key_exists('tahunAkademik', $data) ? $data['tahunAkademik']->getId() : null,
                'kelasSrc' => array_key_exists('kelas', $data) ? $data['kelas']->getId() : null,
                'perulanganSrc' => array_key_exists('perulangan', $data) ? $data['perulangan'] : null,
                'requestUri' => $this->getRequest()->getRequestUri(),
                'mingguanHariKeSrc' => array_key_exists('mingguanHariKe', $data) ? $data['mingguanHariKe'] : null,
                'bulananHariKeSrc' => array_key_exists('bulananHariKe', $data) ? $data['bulananHariKe'] : null,
            ];
        } else {
            $options = [
                'sekolahSrc' => $sekolah->getId(),
                'tahunAkademikSrc' => null,
                'kelasSrc' => null,
                'perulanganSrc' => null,
                'requestUri' => $this->getRequest()->getRequestUri(),
                'mingguanHariKeSrc' => null,
                'bulananHariKeSrc' => null,
            ];
        }
        $duplicateform = $this->createForm('sisdik_salinjadwal', null, $options);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $tokenSekolah = $em->getRepository('LanggasSisdikBundle:TokenSekolah')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        $mesinWakil = $em->getRepository('LanggasSisdikBundle:MesinWakil')
            ->findOneBy([
                'sekolah' => $sekolah,
            ])
        ;

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'duplicateform' => $duplicateform->createView(),
            'perulangan' => $perulangan,
            'displayresult' => $displayresult,
            'searchdata' => $data,
            'daftarPerulangan' => JadwalKehadiran::getDaftarPerulangan(),
            'daynames' => JadwalKehadiran::getNamaHari(),
            'tokenSekolah' => $tokenSekolah,
            'mesinWakil' => $mesinWakil,
        ];
    }

    /**
     * Menggandakan jadwal
     *
     * @Route("/jadwal-kepulangan/salin", name="jadwal_kepulangan_duplicate")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function duplicateSchedule(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('sisdik_salinjadwal', null, ['sekolahSrc' => $sekolah->getId()]);

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwalKepulangan')
            ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwalKepulangan')
            ->leftJoin('jadwalKepulangan.tahunAkademik', 'tahunAkademik')
            ->leftJoin('jadwalKepulangan.kelas', 'kelas')
            ->leftJoin('jadwalKepulangan.templatesms', 'templatesms')
            ->where('jadwalKepulangan.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah->getId())
        ;

        $form->submit($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $requestUri = $data['requestUri'];

            // source
            $tahunAkademikSrc = $data['tahunAkademikSrc'];
            $kelasSrc = $data['kelasSrc'];
            $perulanganSrc = $data['perulanganSrc'];
            $mingguanHariKeSrc = $data['mingguanHariKeSrc'];
            $bulananHariKeSrc = $data['bulananHariKeSrc'];

            // target
            $tahunAkademik = $data['tahunAkademik'];
            $kelas = $data['kelas'];
            $perulangan = $data['perulangan'];
            $mingguanHariKe = $data['mingguanHariKe'];
            $bulananHariKe = $data['bulananHariKe'];

            if ($tahunAkademikSrc != '') {
                $querybuilder->andWhere('tahunAkademik.id = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $tahunAkademikSrc);
            }
            if ($kelasSrc != '') {
                $querybuilder->andWhere('kelas.id = :kelas');
                $querybuilder->setParameter('kelas', $kelasSrc);
            }
            if ($perulanganSrc != '') {
                $querybuilder->andWhere("(jadwalKepulangan.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $perulanganSrc);
            }
            if ($perulanganSrc == 'b-mingguan' && array_key_exists('mingguanHariKe', $data)) {
                $querybuilder->andWhere("(jadwalKepulangan.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $mingguanHariKeSrc);
            }
            if ($perulanganSrc == 'c-bulanan' && array_key_exists('bulananHariKe', $data)) {
                $querybuilder->andWhere("(jadwalKepulangan.bulananHariKe = :bulananHariKe)");
                $querybuilder->setParameter('bulananHariKe', $bulananHariKeSrc);
            }

            $results = $querybuilder->getQuery()->getResult();

            foreach ($results as $result) {
                $entity = new JadwalKepulangan;

                $entity->setSekolah($sekolah);
                $entity->setTahunAkademik($tahunAkademik);
                $entity->setKelas($kelas);
                $entity->setTemplatesms($result->getTemplatesms());

                $entity->setStatusKepulangan($result->getStatusKepulangan());

                $entity->setPerulangan($perulangan);
                if ($perulangan == 'b-mingguan') $entity->setMingguanHariKe($mingguanHariKe);
                if ($perulangan == 'c-bulanan') $entity->setBulananHariKe($bulananHariKe);

                $entity->setParamstatusDariJam($result->getParamstatusDariJam());
                $entity->setParamstatusHinggaJam($result->getParamstatusHinggaJam());
                $entity->setKirimSms($result->isKirimSms());
                $entity->setSmsJam($result->getSmsJam());
                $entity->setOtomatisTerhubungMesin($result->isOtomatisTerhubungMesin());
                $entity->setPermulaan($result->isPermulaan());

                $em->persist($entity);
            }

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.schedule.duplicate.success'))
            ;

            $em->flush();
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.presence.schedule.duplicate.fail'))
            ;
        }

        return $this->redirect($requestUri);
    }

    /**
     * @Route("/jadwal-kepulangan", name="jadwal_kepulangan_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JadwalKepulangan:new.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new JadwalKepulangan;
        $form = $this->createForm('sisdik_jadwalkepulangan', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.jadwal.kepulangan.berhasil.tersimpan'))
            ;

            return $this->redirect($this->generateUrl('jadwal_kepulangan_show', [
                'id' => $entity->getId()
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kepulangan/new/", name="jadwal_kepulangan_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKepulangan;
        $form = $this->createForm('sisdik_jadwalkepulangan', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kepulangan/{id}", name="jadwal_kepulangan_show", requirements={"id"="\d+"})
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKepulangan tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('view', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daftarPerulangan' => JadwalKehadiran::getDaftarPerulangan(),
            'daftarNamaHari' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/jadwal-kepulangan/{id}/edit", name="jadwal_kepulangan_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKepulangan tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $editForm = $this->createForm('sisdik_jadwalkepulangan', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kepulangan/{id}", name="jadwal_kepulangan_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JadwalKepulangan:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKepulangan tak ditemukan.');
        }

        if ($this->get('security.context')->isGranted('edit', $entity) === false) {
            throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_jadwalkepulangan', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.schedule.updated'))
            ;

            return $this->redirect($this->generateUrl('jadwal_kepulangan_edit', [
                'id' => $id,
            ]));
        }

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kepulangan/{id}/delete", name="jadwal_kepulangan_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('LanggasSisdikBundle:JadwalKepulangan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JadwalKepulangan tak ditemukan.');
            }

            if ($this->get('security.context')->isGranted('delete', $entity) === false) {
                throw new AccessDeniedException($this->get('translator')->trans('akses.ditolak'));
            }

            $em->remove($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.schedule.deleted'))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('jadwal_kepulangan_list'));
    }

    /**
     * API untuk mengunduh jadwal kepulangan berdasarkan token mesin wakil
     *
     * @Route("/_api/jadwal-kepulangan/{token}.{_format}", name="jadwal_kepulangan_unduh-jadwal", defaults={"_format"="json"}, requirements={"_format"="(xml|json)"})
     * @Method("GET")
     */
    public function unduhJadwalKepulanganAction($token)
    {
        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:TokenSekolah')->findByMesinProxy($token);

        if (count($entity) == 1) {
            $tokenSekolah = $entity[0];
            if ($tokenSekolah instanceof TokenSekolah) {
                $sekolah = $tokenSekolah->getSekolah();

                $jadwal = [];
                foreach (JadwalKehadiran::getDaftarPerulangan() as $key => $value) {
                    $querybuilder = $em->createQueryBuilder()
                        ->select('jadwalKepulangan')
                        ->from('LanggasSisdikBundle:JadwalKepulangan', 'jadwalKepulangan')
                        ->leftJoin('jadwalKepulangan.tahunAkademik', 'tahunAkademik')
                        ->where('jadwalKepulangan.sekolah = :sekolah')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->andWhere('jadwalKepulangan.perulangan = :perulangan')
                        ->andWhere("jadwalKepulangan.paramstatusDariJam != '' OR jadwalKepulangan.paramstatusHinggaJam != ''")
                        ->addOrderBy('jadwalKepulangan.mingguanHariKe', 'ASC')
                        ->addOrderBy('jadwalKepulangan.paramstatusDariJam', 'ASC')
                        ->addOrderBy('jadwalKepulangan.paramstatusHinggaJam', 'DESC')
                        ->addOrderBy('jadwalKepulangan.bulananHariKe')
                        ->setParameter('sekolah', $sekolah)
                        ->setParameter('aktif', true)
                        ->setParameter('perulangan', $key)
                    ;

                    $result = $querybuilder->getQuery()->getResult();
                    if ($result) {
                        $jadwal[$key] = $result;
                    }
                }

                $querybuilder2 = $em->createQueryBuilder()
                    ->select('mesinKehadiran')
                    ->from('LanggasSisdikBundle:MesinKehadiran', 'mesinKehadiran')
                    ->where('mesinKehadiran.sekolah = :sekolah')
                    ->setParameter('sekolah', $sekolah->getId());
                $mesinKehadiran = $querybuilder2->getQuery()->getResult();

                $userAdmin = $em->getRepository('LanggasSisdikBundle:User')->findByRole($sekolah, 'ROLE_ADMIN');

                return [
                    'jadwal' => $jadwal,
                    'mesinKehadiran' => $mesinKehadiran,
                    'userAdmin' => $userAdmin,
                ];
            }
        } else {
            throw new NotFoundHttpException($this->get('translator')->trans('exception.token.tak.sahih'));
        }
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder([
                'id' => $id
            ])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $translator = $this->get('translator');

        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$translator->trans('headings.presence', [], 'navigations')][$translator->trans('links.jadwal.kepulangan', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
