<?php
namespace Langgas\SisdikBundle\Controller;

use Langgas\SisdikBundle\Entity\JadwalKehadiran;
use Langgas\SisdikBundle\Form\JadwalKehadiranType;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Form\JadwalKehadiranSearchType;
use Langgas\SisdikBundle\Form\JadwalKehadiranDuplicateType;
use Langgas\SisdikBundle\Entity\TokenSekolah;
use FOS\RestBundle\Controller\FOSRestController as Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new JadwalKehadiranSearchType($this->container, $perulangan));

        $querybuilder = $em->createQueryBuilder()
            ->select('jadwalKehadiran')
            ->from('LanggasSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
            ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
            ->leftJoin('jadwalKehadiran.kelas', 'kelas')
            ->leftJoin('jadwalKehadiran.templatesms', 'templatesms')
            ->where('jadwalKehadiran.sekolah = :sekolah')
            ->setParameter('sekolah', $sekolah->getId())
            ->addOrderBy('jadwalKehadiran.permulaan', 'DESC')
            ->addOrderBy('jadwalKehadiran.statusKehadiran', 'ASC')
            ->addOrderBy('jadwalKehadiran.paramstatusDariJam', 'ASC')
        ;

        $searchform->submit($this->getRequest());

        $data = [];
        $displayresult = false;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] != '') {
                $querybuilder->andWhere('tahunAkademik.id = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $searchdata['tahunAkademik']->getId());
                $data['tahunAkademik'] = $searchdata['tahunAkademik'];
            }
            if ($searchdata['kelas'] != '') {
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

        if (count($data) > 0) {
            $duplicatetype = new JadwalKehadiranDuplicateType(
                $this->container,
                $sekolah->getId(),
                array_key_exists('tahunAkademik', $data) ? $data['tahunAkademik']->getId() : null,
                array_key_exists('kelas', $data) ? $data['kelas']->getId() : null,
                array_key_exists('perulangan', $data) ? $data['perulangan'] : null,
                $this->getRequest()->getRequestUri(),
                array_key_exists('mingguanHariKe', $data) ? $data['mingguanHariKe'] : null,
                array_key_exists('bulananHariKe', $data) ? $data['bulananHariKe'] : null
            );
        } else {
            $duplicatetype = new JadwalKehadiranDuplicateType(
                $this->container,
                $sekolah->getId(),
                null,
                null,
                null,
                $this->getRequest()->getRequestUri(),
                null,
                null
            );
        }
        $duplicateform = $this->createForm($duplicatetype);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return [
            'pagination' => $pagination,
            'searchform' => $searchform->createView(),
            'duplicateform' => $duplicateform->createView(),
            'perulangan' => $perulangan,
            'displayresult' => $displayresult,
            'searchdata' => $data,
            'daftarPerulangan' => JadwalKehadiran::getDaftarPerulangan(),
            'daynames' => JadwalKehadiran::getNamaHari()
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
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new JadwalKehadiranDuplicateType($this->container, $sekolah->getId()));

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
                $entity = new JadwalKehadiran();

                $entity->setSekolah($sekolah);
                $entity->setTahunAkademik($tahunAkademik);
                $entity->setKelas($kelas);
                $entity->setTemplatesms($result->getTemplatesms());

                $entity->setStatusKehadiran($result->getStatusKehadiran());

                $entity->setPerulangan($perulangan);
                if ($perulangan == 'b-mingguan')
                    $entity->setMingguanHariKe($mingguanHariKe);
                if ($perulangan == 'c-bulanan')
                    $entity->setBulananHariKe($bulananHariKe);

                $entity->setParamstatusDariJam($result->getParamstatusDariJam());
                $entity->setParamstatusHinggaJam($result->getParamstatusHinggaJam());
                $entity->setKirimSms($result->isKirimSms());
                $entity->setSmsJam($result->getSmsJam());
                $entity->setOtomatisTerhubungMesin($result->isOtomatisTerhubungMesin());
                $entity->setPermulaan($result->isPermulaan());

                $em->persist($entity);
            }

            $this->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')
                ->trans('flash.presence.schedule.duplicate.success'))
            ;

            $em->flush();
        } else {
            $this->get('session')
                ->getFlashBag()
                ->add('error', $this->get('translator')
                ->trans('flash.presence.schedule.duplicate.fail'))
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JadwalKehadiran();
        $form = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')
                ->trans('flash.presence.schedule.inserted'))
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKehadiran();
        $form = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);

        return [
            'entity' => $entity,
            'form' => $form->createView(),
            'sekolah' => $sekolah,
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $editForm = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LanggasSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')
                ->trans('flash.presence.schedule.updated'))
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
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
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
                ->add('success', $this->get('translator')
                ->trans('flash.presence.schedule.deleted'))
            ;
        } else {
            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')
                ->trans('flash.presence.fail.delete'))
            ;
        }

        return $this->redirect($this->generateUrl('jadwal_kehadiran_list', [
            'page' => $this->getRequest()->get('page')
        ]));
    }

    /**
     * API untuk mengunduh jadwal kehadiran berdasarkan token mesin wakil
     *
     * @Route("/_api/jadwal-kehadiran/{token}.{_format}", name="jadwal_kehadiran_unduh-jadwal", defaults={"_format"="json"}, requirements={"_format"="(xml|json)"})
     * @Method("GET")
     */
    public function unduhJadwalKehadiranAction($token)
    {
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
                        ->setParameter('sekolah', $sekolah->getId())
                        ->andWhere('tahunAkademik.aktif = :aktif')
                        ->setParameter('aktif', true)
                        ->andWhere('jadwalKehadiran.perulangan = :perulangan')
                        ->setParameter('perulangan', $key)
                        ->andWhere("jadwalKehadiran.paramstatusDariJam != '' OR jadwalKehadiran.paramstatusHinggaJam != ''")
                        ->addOrderBy('jadwalKehadiran.mingguanHariKe', 'ASC')
                        ->addOrderBy('jadwalKehadiran.paramstatusDariJam', 'ASC')
                        ->addOrderBy('jadwalKehadiran.paramstatusHinggaJam', 'DESC')
                        ->addOrderBy('jadwalKehadiran.bulananHariKe');

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

                return [
                    'jadwal' => $jadwal,
                    'mesinKehadiran' => $mesinKehadiran,
                ];
            }
        } else {
            throw new NotFoundHttpException($this->get('translator')->trans('exception.token.tak.sahih'));
        }
    }

    private function createDeleteForm($id)
    {
        return $this
            ->createFormBuilder(['id' => $id])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.presence', [], 'navigations')][$this->get('translator')->trans('links.jadwal.kehadiran', [], 'navigations')]->setCurrent(true);
    }

    private function isRegisteredToSchool()
    {
        $user = $this->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } elseif ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException($this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
