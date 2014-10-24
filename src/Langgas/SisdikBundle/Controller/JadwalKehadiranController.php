<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController as Controller;
use Langgas\SisdikBundle\Form\JadwalKehadiranType;
use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Entity\TokenSekolah;
use Langgas\SisdikBundle\Entity\TahunAkademik;
use Langgas\SisdikBundle\Entity\Kelas;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * @Route("/")
 */
class JadwalKehadiranController extends Controller
{
    /**
     * @Route("/jadwal-kehadiran", name="jadwal_kehadiran")
     * @Method("GET")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('jadwal_kehadiran_list'));
    }

    /**
     * @Route("/jadwal-kehadiran/daftar/{perulangan}", name="jadwal_kehadiran_list")
     * @Method("GET")
     * @Template("LanggasSisdikBundle:JadwalKehadiran:index.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function listAction($perulangan = 'harian')
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm('sisdik_carijadwalkehadiran', ['perulangan' => $perulangan]);

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwalKehadiran')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
            ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
            ->leftJoin('jadwalKehadiran.kelas', 'kelas')
            ->leftJoin('jadwalKehadiran.templatesms', 'templatesms')
            ->where('jadwalKehadiran.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah)
            ->addOrderBy('jadwalKehadiran.permulaan', 'DESC')
            ->addOrderBy('jadwalKehadiran.statusKehadiran', 'ASC')
            ->addOrderBy('jadwalKehadiran.paramstatusDariJam', 'ASC')
        ;

        $searchform->submit($this->getRequest());

        $data = [];
        $displayresult = false;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] instanceof TahunAkademik) {
                $querybuilder->andWhere('tahunAkademik.id = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $searchdata['tahunAkademik']->getId());
                $data['tahunAkademik'] = $searchdata['tahunAkademik'];
            }
            if ($searchdata['kelas'] instanceof Kelas) {
                $querybuilder->andWhere('kelas.id = :kelas');
                $querybuilder->setParameter('kelas', $searchdata['kelas']->getId());
                $data['kelas'] = $searchdata['kelas'];
            }
            if ($searchdata['perulangan'] != '') {
                $querybuilder->andWhere("(jadwalKehadiran.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $searchdata['perulangan']);
                $data['perulangan'] = $searchdata['perulangan'];

                $perulangan = $searchdata['perulangan'];
                $displayresult = true;
            }

            if ($searchdata['perulangan'] == 'b-mingguan' && array_key_exists('mingguanHariKe', $searchdata)) {
                $querybuilder->andWhere("(jadwalKehadiran.mingguanHariKe = :mingguanHariKe)");
                if ($searchdata['mingguanHariKe'] === null) {
                    $searchdata['mingguanHariKe'] = 0;
                }
                $querybuilder->setParameter('mingguanHariKe', $searchdata['mingguanHariKe']);
                $data['mingguanHariKe'] = $searchdata['mingguanHariKe'];

                $displayresult = true;
            }

            if ($searchdata['perulangan'] == 'c-bulanan' && array_key_exists('bulananHariKe', $searchdata)) {
                $querybuilder->andWhere("(jadwalKehadiran.bulananHariKe = :bulananHariKe)");
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
        $duplicateform = $this->createForm('sisdik_jadwalkehadiran_salin', null, $options);

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
     * @Route("/jadwal-kehadiran/duplicateschedule", name="jadwal_kehadiran_duplicate")
     * @Method("POST")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function duplicateSchedule(Request $request)
    {
        $sekolah = $this->getSekolah();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('sisdik_jadwalkehadiran_salin', null, ['sekolahSrc' => $sekolah->getId()]);

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwalKehadiran')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
            ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
            ->leftJoin('jadwalKehadiran.kelas', 'kelas')
            ->leftJoin('jadwalKehadiran.templatesms', 'templatesms')
            ->where('jadwalKehadiran.sekolah = :sekolah')
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
                $querybuilder->andWhere("(jadwalKehadiran.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $perulanganSrc);
            }
            if ($perulanganSrc == 'b-mingguan' && array_key_exists('mingguanHariKe', $data)) {
                $querybuilder->andWhere("(jadwalKehadiran.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $mingguanHariKeSrc);
            }
            if ($perulanganSrc == 'c-bulanan' && array_key_exists('bulananHariKe', $data)) {
                $querybuilder->andWhere("(jadwalKehadiran.bulananHariKe = :bulananHariKe)");
                $querybuilder->setParameter('bulananHariKe', $bulananHariKeSrc);
            }

            $results = $querybuilder->getQuery()->getResult();

            foreach ($results as $result) {
                $entity = new JadwalKehadiran;

                $entity->setSekolah($sekolah);
                $entity->setTahunAkademik($tahunAkademik);
                $entity->setKelas($kelas);
                $entity->setTemplatesms($result->getTemplatesms());

                $entity->setStatusKehadiran($result->getStatusKehadiran());

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
     * @Route("/jadwal-kehadiran", name="jadwal_kehadiran_create")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JadwalKehadiran:new.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function createAction(Request $request)
    {
        $this->setCurrentMenu();

        $entity = new JadwalKehadiran;
        $form = $this->createForm('sisdik_jadwalkehadiran', $entity);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.schedule.inserted'))
            ;

            return $this->redirect($this->generateUrl('jadwal_kehadiran_show', [
                'id' => $entity->getId()
            ]));
        }

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kehadiran/new/", name="jadwal_kehadiran_new")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function newAction()
    {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKehadiran;
        $form = $this->createForm('sisdik_jadwalkehadiran', $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kehadiran/{id}", name="jadwal_kehadiran_show", requirements={"id"="\d+"})
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function showAction($id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
            'daynames' => JadwalKehadiran::getNamaHari(),
        ];
    }

    /**
     * @Route("/jadwal-kehadiran/{id}/edit", name="jadwal_kehadiran_edit")
     * @Method("GET")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function editAction($id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $editForm = $this->createForm('sisdik_jadwalkehadiran', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ];
    }

    /**
     * @Route("/jadwal-kehadiran/{id}", name="jadwal_kehadiran_update")
     * @Method("POST")
     * @Template("LanggasSisdikBundle:JadwalKehadiran:edit.html.twig")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function updateAction(Request $request, $id)
    {
        $this->setCurrentMenu();

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm('sisdik_jadwalkehadiran', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.presence.schedule.updated'))
            ;

            return $this->redirect($this->generateUrl('jadwal_kehadiran_edit', [
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
     * @Route("/jadwal-kehadiran/{id}/delete", name="jadwal_kehadiran_delete")
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
            $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
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

        return $this->redirect($this->generateUrl('jadwal_kehadiran_list'));
    }

    /**
     * API untuk mengunduh jadwal kehadiran berdasarkan token mesin wakil
     *
     * @Route("/_api/jadwal-kehadiran/{token}.{_format}", name="jadwal_kehadiran_unduh-jadwal", defaults={"_format"="json"}, requirements={"_format"="(xml|json)"})
     * @Method("GET")
     */
    public function unduhJadwalKehadiranAction($token)
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
                        ->select('jadwalKehadiran')
                        ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
                        ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
                        ->where('jadwalKehadiran.sekolah = :sekolah')
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->andWhere('jadwalKehadiran.perulangan = :perulangan')
                        ->andWhere("jadwalKehadiran.paramstatusDariJam != '' OR jadwalKehadiran.paramstatusHinggaJam != ''")
                        ->addOrderBy('jadwalKehadiran.mingguanHariKe', 'ASC')
                        ->addOrderBy('jadwalKehadiran.paramstatusDariJam', 'ASC')
                        ->addOrderBy('jadwalKehadiran.paramstatusHinggaJam', 'DESC')
                        ->addOrderBy('jadwalKehadiran.bulananHariKe')
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
        $menu[$translator->trans('headings.presence', [], 'navigations')][$translator->trans('links.jadwal.kehadiran', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
