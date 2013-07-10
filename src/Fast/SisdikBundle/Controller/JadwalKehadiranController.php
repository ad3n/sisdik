<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\JadwalKehadiran;
use Fast\SisdikBundle\Form\JadwalKehadiranType;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\JadwalKehadiranSearchType;
use Fast\SisdikBundle\Form\JadwalKehadiranDuplicateType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * JadwalKehadiran controller.
 *
 * @Route("/jadwal-kehadiran")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class JadwalKehadiranController extends Controller
{
    /**
     * Lists all JadwalKehadiranKepulangan entities.
     *
     * @Route("/", name="jadwal_kehadiran")
     * @Method("GET")
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('jadwal_kehadiran_list'));
    }

    /**
     * Lists all JadwalKehadiran entities.
     *
     * @Route("/daftar/{perulangan}", name="jadwal_kehadiran_list")
     * @Method("GET")
     * @Template("FastSisdikBundle:JadwalKehadiran:index.html.twig")
     */
    public function listAction($perulangan = 'harian') {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this->createForm(new JadwalKehadiranSearchType($this->container, $perulangan));

        $querybuilder = $em->createQueryBuilder()->select('jadwalKehadiran')
                ->from('FastSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
                ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
                ->leftJoin('jadwalKehadiran.kelas', 'kelas')
                ->leftJoin('jadwalKehadiran.templatesms', 'templatesms')
                ->where('jadwalKehadiran.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId())
                ->addOrderBy('jadwalKehadiran.statusKehadiran', 'ASC');

        $searchform->submit($this->getRequest());

        $data = array();
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
                var_dump($searchdata['mingguanHariKe']);
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
            $duplicatetype = new JadwalKehadiranDuplicateType($this->container, $sekolah->getId(),
                    array_key_exists('tahunAkademik', $data) ? $data['tahunAkademik']->getId() : null,
                    array_key_exists('kelas', $data) ? $data['kelas']->getId() : null,
                    array_key_exists('perulangan', $data) ? $data['perulangan'] : null,
                    $this->getRequest()->getRequestUri(),
                    array_key_exists('mingguanHariKe', $data) ? $data['mingguanHariKe'] : null,
                    array_key_exists('bulananHariKe', $data) ? $data['bulananHariKe'] : null);
        } else {
            $duplicatetype = new JadwalKehadiranDuplicateType($this->container, $sekolah->getId(), null,
                    null, null, $this->getRequest()->getRequestUri(), null, null);
        }
        $duplicateform = $this->createForm($duplicatetype);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        return array(
                'pagination' => $pagination, 'searchform' => $searchform->createView(),
                'duplicateform' => $duplicateform->createView(), 'perulangan' => $perulangan,
                'displayresult' => $displayresult, 'searchdata' => $data,
                'daftarPerulangan' => JadwalKehadiran::getDaftarPerulangan(),
                'daynames' => JadwalKehadiran::getNamaHari()
        );
    }

    /**
     * Duplicate schedule
     *
     * @Route("/duplicateschedule", name="jadwal_kehadiran_duplicate")
     * @Method("POST")
     */
    public function duplicateSchedule(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new JadwalKehadiranDuplicateType($this->container, $sekolah->getId()));

        $querybuilder = $em->createQueryBuilder()->select('jadwalKehadiran')
                ->from('FastSisdikBundle:JadwalKehadiran', 'jadwalKehadiran')
                ->leftJoin('jadwalKehadiran.tahunAkademik', 'tahunAkademik')
                ->leftJoin('jadwalKehadiran.kelas', 'kelas')
                ->leftJoin('jadwalKehadiran.templatesms', 'templatesms')
                ->where('jadwalKehadiran.sekolah = :sekolah')->setParameter('sekolah', $sekolah->getId());

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
            if ($perulanganSrc == 'mingguan' && array_key_exists('mingguanHariKe', $data)) {
                $querybuilder->andWhere("(jadwalKehadiran.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $mingguanHariKeSrc);
            }
            if ($perulanganSrc == 'bulanan' && array_key_exists('bulananHariKe', $data)) {
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
                if ($perulangan == 'mingguan')
                    $entity->setMingguanHariKe($mingguanHariKe);
                if ($perulangan == 'bulanan')
                    $entity->setBulananHariKe($bulananHariKe);

                $entity->setParamstatusDariJam($result->getParamstatusDariJam());
                $entity->setParamstatusHinggaJam($result->getParamstatusHinggaJam());
                $entity->setKirimSms($result->isKirimSms());
                $entity->setSmsJam($result->getSmsJam());
                $entity->setOtomatisTerhubungMesin($result->isOtomatisTerhubungMesin());

                $em->persist($entity);

            }

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')->trans('flash.presence.schedule.duplicate.success'));

            $em->flush();
        } else {
            $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('flash.presence.schedule.duplicate.fail'));
        }

        return $this->redirect($requestUri);
    }

    /**
     * Creates a new JadwalKehadiran entity.
     *
     * @Route("/", name="jadwal_kehadiran_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiran:new.html.twig")
     */
    public function createAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JadwalKehadiran();
        $form = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.schedule.inserted'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('jadwal_kehadiran_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new JadwalKehadiran entity.
     *
     * @Route("/new/", name="jadwal_kehadiran_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKehadiran();
        $form = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(), 'sekolah' => $sekolah
        );
    }

    /**
     * Finds and displays a JadwalKehadiran entity.
     *
     * @Route("/{id}", name="jadwal_kehadiran_show", requirements={"id"="\d+"})
     * @Method("GET")
     * @Template()
     */
    public function showAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'daynames' => JadwalKehadiran::getNamaHari()
        );
    }

    /**
     * Displays a form to edit an existing JadwalKehadiran entity.
     *
     * @Route("/{id}/edit", name="jadwal_kehadiran_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $editForm = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing JadwalKehadiran entity.
     *
     * @Route("/{id}", name="jadwal_kehadiran_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiran:edit.html.twig")
     */
    public function updateAction(Request $request, $id) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiran')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JadwalKehadiranType($this->container, $sekolah->getId()), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.schedule.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('jadwal_kehadiran_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah->getId())
        );
    }

    /**
     * Deletes a JadwalKehadiran entity.
     *
     * @Route("/{id}/delete", name="jadwal_kehadiran_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, $id) {
        $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiran')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JadwalKehadiran tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.schedule.deleted'));
        } else {
            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('jadwal_kehadiran_list',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.presence']['links.jadwal.kehadiran']->setCurrent(true);
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
