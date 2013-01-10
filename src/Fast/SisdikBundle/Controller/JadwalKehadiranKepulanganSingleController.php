<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\JadwalKehadiranKepulangan;
use Fast\SisdikBundle\Form\JadwalKehadiranKepulanganType;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Controller\SekolahList;
use Fast\SisdikBundle\Form\JadwalKehadiranKepulanganSearchType;
use Fast\SisdikBundle\Form\JadwalKehadiranKepulanganDuplicateType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * JadwalKehadiranKepulangan controller.
 *
 * @Route("/presence/schedule/single")
 * @PreAuthorize("hasRole('ROLE_ADMIN')")
 */
class JadwalKehadiranKepulanganSingleController extends Controller
{
    /**
     * Lists all JadwalKehadiranKepulangan entities.
     *
     * @Route("/", name="presence_schedule_single")
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('presence_schedule_single_list'));
    }

    /**
     * Lists all JadwalKehadiranKepulangan entities.
     *
     * @Route("/list/{repetition}", name="presence_schedule_single_list", defaults={"repetition"="harian"})
     * @Template("FastSisdikBundle:JadwalKehadiranKepulanganSingle:index.html.twig")
     */
    public function listAction($repetition) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this
                ->createForm(
                        new JadwalKehadiranKepulanganSearchType($this->container, $idsekolah,
                                $repetition));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')
                ->leftJoin('t.idtahun', 't1')->leftJoin('t.idkelas', 't2')
                ->leftJoin('t.idstatusKehadiranKepulangan', 't3')
                ->leftJoin('t.idtemplatesms', 't4')->where('t1.idsekolah = :idsekolah')
                ->addOrderBy('t3.nama', 'ASC');

        $querybuilder->setParameter('idsekolah', $idsekolah);

        $searchform->bind($this->getRequest());

        $displayresult = false;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['idtahun'] != '') {
                $querybuilder->andWhere('t1.id = :idtahun');
                $querybuilder->setParameter('idtahun', $searchdata['idtahun']);
            }
            if ($searchdata['idkelas'] != '') {
                $querybuilder->andWhere('t2.id = :idkelas');
                $querybuilder->setParameter('idkelas', $searchdata['idkelas']);
            }
            if ($searchdata['perulangan'] != '') {
                $querybuilder->andWhere("(t.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $searchdata['perulangan']);

                $repetition = $searchdata['perulangan'];

                if ($searchdata['perulangan'] == 'harian') {
                    $displayresult = true;
                }
            }

            if ($searchdata['idtahun'] != '' && $searchdata['idkelas'] != ''
                    && $searchdata['perulangan'] != '') {
                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container,
                        $idsekolah->getId(), $searchdata['idtahun']->getId(),
                        $searchdata['idkelas']->getId(), $searchdata['perulangan'],
                        $this->getRequest()->getRequestUri());
            } else {
                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container,
                        $idsekolah->getId(), null, null, null,
                        $this->getRequest()->getRequestUri());
            }

            $data = array(
                    'idtahun' => $searchdata['idtahun'], 'idkelas' => $searchdata['idkelas'],
                    'perulangan' => $searchdata['perulangan'],
            );

            if ($searchdata['perulangan'] == 'mingguan'
                    && array_key_exists('mingguanHariKe', $searchdata)) {
                $querybuilder->andWhere("(t.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $searchdata['mingguanHariKe']);
                $data['mingguanHariKe'] = $searchdata['mingguanHariKe'];
                $displayresult = true;

                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container,
                        $idsekolah->getId(), $searchdata['idtahun']->getId(),
                        $searchdata['idkelas']->getId(), $searchdata['perulangan'],
                        $this->getRequest()->getRequestUri(), $searchdata['mingguanHariKe']);
            }

            if ($searchdata['perulangan'] == 'bulanan'
                    && array_key_exists('bulananHariKe', $searchdata)) {
                $querybuilder->andWhere("(t.bulananHariKe = :bulananHariKe)");
                $querybuilder->setParameter('bulananHariKe', $searchdata['bulananHariKe']);
                $data['bulananHariKe'] = $searchdata['bulananHariKe'];
                $displayresult = true;

                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container,
                        $idsekolah->getId(), $searchdata['idtahun']->getId(),
                        $searchdata['idkelas']->getId(), $searchdata['perulangan'],
                        $this->getRequest()->getRequestUri(), null, $searchdata['bulananHariKe']);
            }

            $duplicateform = $this->createForm($duplicatetype);
        }

        // recreate form
        $searchform = $this
                ->createForm(
                        new JadwalKehadiranKepulanganSearchType($this->container, $idsekolah,
                                $searchdata['perulangan']), $data);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator
                ->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $sekolahlist = new SekolahList($this->container);
        $sekolahKehadiranList = $sekolahlist->buildSekolahList();

        return array(
                'pagination' => $pagination, 'schools' => $sekolahKehadiranList,
                'idsekolah' => $idsekolah, 'searchform' => $searchform->createView(),
                'repetition' => $repetition, 'displayresult' => $displayresult,
                'searchdata' => $data, 'duplicateform' => $duplicateform->createView(),
                'daynames' => $this->buildDayNames()
        );
    }

    /**
     * Finds and displays a JadwalKehadiranKepulangan entity.
     *
     * @Route("/{id}/show", name="presence_schedule_single_show")
     * @Template()
     */
    public function showAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'daynames' => $this->buildDayNames()
        );
    }

    /**
     * Displays a form to create a new JadwalKehadiranKepulangan entity.
     *
     * @Route("/new/", name="presence_schedule_single_new")
     * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function newAction() {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKehadiranKepulangan();
        $form = $this
                ->createForm(
                        new JadwalKehadiranKepulanganType($this->container, $idsekolah->getId()),
                        $entity);

        return array(
            'entity' => $entity, 'form' => $form->createView(), 'idsekolah' => $idsekolah
        );
    }

    /**
     * Creates a new JadwalKehadiranKepulangan entity.
     *
     * @Route("/create", name="presence_schedule_single_create")
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiranKepulanganSingle:new.html.twig")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function createAction(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $entity = new JadwalKehadiranKepulangan();
        $form = $this
                ->createForm(
                        new JadwalKehadiranKepulanganType($this->container, $idsekolah->getId()),
                        $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.presence.schedule.inserted'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_schedule_single_show',
                                            array(
                                                'id' => $entity->getId()
                                            )));
        }

        return array(
            'entity' => $entity, 'form' => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing JadwalKehadiranKepulangan entity.
     *
     * @Route("/{id}/edit", name="presence_schedule_single_edit")
     * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function editAction($id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $editForm = $this
                ->createForm(
                        new JadwalKehadiranKepulanganType($this->container, $idsekolah->getId()),
                        $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing JadwalKehadiranKepulangan entity.
     *
     * @Route("/{id}/update", name="presence_schedule_single_update")
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiranKepulangan:edit.html.twig")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function updateAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this
                ->createForm(new JadwalKehadiranKepulanganType($this->container, $idsekolah),
                        $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.presence.schedule.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_schedule_single_edit',
                                            array(
                                                'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'idsekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($idsekolah)
        );
    }

    /**
     * Deletes a JadwalKehadiranKepulangan entity.
     *
     * @Route("/{id}/delete", name="presence_schedule_single_delete")
     * @Method("POST")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteAction(Request $request, $id) {
        $idsekolah = $this->isRegisteredToSchool();

        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

            if (!$entity) {
                throw $this
                        ->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.presence.schedule.deleted'));
        } else {
            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')->trans('flash.presence.fail.delete'));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('presence_schedule_single_list',
                                        array(
                                            'page' => $this->getRequest()->get('page')
                                        )));
    }

    /**
     * Duplicate schedule
     * 
     * @Route("/duplicateschedule", name="presence_schedule_single_duplicate")
     * @Method("POST")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function duplicateSchedule(Request $request) {
        $idsekolah = $this->isRegisteredToSchool();

        $em = $this->getDoctrine()->getManager();

        $form = $this
                ->createForm(
                        new JadwalKehadiranKepulanganDuplicateType($this->container, $idsekolah));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')
                ->leftJoin('t.idtahun', 't1')->leftJoin('t.idkelas', 't2')
                ->leftJoin('t.idstatusKehadiranKepulangan', 't3')
                ->leftJoin('t.idtemplatesms', 't4')->where('t1.idsekolah = :idsekolah')
                ->addOrderBy('t3.nama', 'ASC');

        $querybuilder->setParameter('idsekolah', $idsekolah);

        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $requestUri = $data['requestUri'];

            // source
            $idtahunSrc = $data['idtahunSrc'];
            $idkelasSrc = $data['idkelasSrc'];
            $perulanganSrc = $data['perulanganSrc'];
            $mingguanHariKeSrc = $data['mingguanHariKeSrc'];
            $bulananHariKeSrc = $data['bulananHariKeSrc'];

            // target
            $idtahun = $data['idtahun'];
            $idkelas = $data['idkelas'];
            $perulangan = $data['perulangan'];
            $mingguanHariKe = $data['mingguanHariKe'];
            $bulananHariKe = $data['bulananHariKe'];

            if ($idtahunSrc != '') {
                $querybuilder->andWhere('t1.id = :idtahun');
                $querybuilder->setParameter('idtahun', $idtahunSrc);
            }
            if ($idkelasSrc != '') {
                $querybuilder->andWhere('t2.id = :idkelas');
                $querybuilder->setParameter('idkelas', $idkelasSrc);
            }
            if ($perulanganSrc != '') {
                $querybuilder->andWhere("(t.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $perulanganSrc);
            }
            if ($perulanganSrc == 'mingguan' && array_key_exists('mingguanHariKe', $data)) {
                $querybuilder->andWhere("(t.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $mingguanHariKeSrc);
            }
            if ($perulanganSrc == 'bulanan' && array_key_exists('bulananHariKe', $data)) {
                $querybuilder->andWhere("(t.bulananHariKe = :bulananHariKe)");
                $querybuilder->setParameter('bulananHariKe', $bulananHariKeSrc);
            }

            $results = $querybuilder->getQuery()->getResult();

            foreach ($results as $result) {
                $entity = new JadwalKehadiranKepulangan();

                $entity->setIdtahun($idtahun);
                $entity->setIdkelas($idkelas);
                $entity->setPerulangan($perulangan);
                if ($perulangan == 'mingguan')
                    $entity->setMingguanHariKe($mingguanHariKe);
                if ($perulangan == 'bulanan')
                    $entity->setBulananHariKe($bulananHariKe);

                $entity->setCommandJadwal($result->getCommandJadwal());
                $entity->setCommandMassal($result->getCommandMassal());
                $entity->setCommandRealtime($result->getCommandRealtime());
                $entity->setIdstatusKehadiranKepulangan($result->getIdstatusKehadiranKepulangan());
                $entity->setIdtemplatesms($result->getIdtemplatesms());
                $entity->setDariJam($result->getDariJam());
                $entity->setHinggaJam($result->getHinggaJam());
                $entity->setKirimSmsMassal($result->getKirimSmsMassal());
                $entity->setKirimSmsRealtime($result->getKirimSmsRealtime());
                $entity->setParamstatusDariJam($result->getParamstatusDariJam());
                $entity->setParamstatusHinggaJam($result->getParamstatusHinggaJam());
                $entity->setSmsMassalJam($result->getSmsMassalJam());

                $em->persist($entity);

            }

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.presence.schedule.duplicate.success'));

            $em->flush();
        } else {
            $this->get('session')
                    ->setFlash('error',
                            $this->get('translator')
                                    ->trans('flash.presence.schedule.duplicate.fail'));
        }

        return $this->redirect($requestUri);
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.presence']['links.presenceschedule']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $idsekolah = $user->getIdsekolah();

        if (is_object($idsekolah) && $idsekolah instanceof Sekolah) {
            return $idsekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }

    /**
     * build day names
     *
     * @return array
     */
    public function buildDayNames() {
        return array(
                0 => $this->get('translator')->trans('label.sunday'),
                $this->get('translator')->trans('label.monday'),
                $this->get('translator')->trans('label.tuesday'),
                $this->get('translator')->trans('label.wednesday'),
                $this->get('translator')->trans('label.thursday'),
                $this->get('translator')->trans('label.friday'),
                $this->get('translator')->trans('label.saturday'),
        );
    }
}
