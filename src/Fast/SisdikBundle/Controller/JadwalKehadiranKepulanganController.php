<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
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
use Fast\SisdikBundle\Form\JadwalKehadiranKepulanganCommandType;
use Fast\SisdikBundle\Form\JadwalKehadiranKepulanganDuplicateType;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * JadwalKehadiranKepulangan controller.
 *
 * @Route("/presence/schedule")
 * @PreAuthorize("hasRole('ROLE_SUPER_ADMIN')")
 */
class JadwalKehadiranKepulanganController extends Controller
{
    const INTERVAL = 5;
    const SCHEDULE_DIR = "/schedules/";
    const SCHEDULE_FILE = "allschedule";
    const SCHEDULE_PREFIX = "schedule";
    const FP_SCHEDULE_FILE = "schedule-fp";
    const SMS_SCHEDULE_REALTIME_FILE = "schedule-sr";
    const SMS_SCHEDULE_MASSIVE_FILE = "schedule-sm";
    const COMMAND_PARAMETER = "--env=prod";

    /**
     * Lists all JadwalKehadiranKepulangan entities.
     *
     * @Route("/", name="presence_schedule")
     * @Template()
     */
    public function indexAction() {
        $sekolahlist = new SekolahList($this->container);
        $sekolahKehadiranList = $sekolahlist->buildSekolahList();

        $sekolah = key($sekolahKehadiranList);

        return $this
                ->redirect(
                        $this
                                ->generateUrl('presence_schedule_list',
                                        array(
                                            'sekolah' => $sekolah
                                        )));
    }

    /**
     * Lists all JadwalKehadiranKepulangan entities, filtered by school and repetition
     *
     * @Route("/{sekolah}/list/{repetition}", name="presence_schedule_list",
     *         requirements={"sekolah"="\d+"}, defaults={"repetition"="harian"})
     * @Template()
     */
    public function listAction($sekolah, $repetition) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $searchform = $this
                ->createForm(new JadwalKehadiranKepulanganSearchType($this->container, $sekolah, $repetition));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->leftJoin('t.tahunAkademik', 't1')
                ->leftJoin('t.kelas', 't2')->leftJoin('t.statusKehadiranKepulangan', 't3')
                ->leftJoin('t.templatesms', 't4')->where('t1.sekolah = :sekolah')
                ->addOrderBy('t3.nama', 'ASC');
        $querybuilder->setParameter('sekolah', $sekolah);

        $searchform->bind($this->getRequest());

        $displayresult = false;
        if ($searchform->isValid()) {
            $searchdata = $searchform->getData();

            if ($searchdata['tahunAkademik'] != '') {
                $querybuilder->andWhere('t1.id = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $searchdata['tahunAkademik']->getId());
            }
            if ($searchdata['kelas'] != '') {
                $querybuilder->andWhere('t2.id = :kelas');
                $querybuilder->setParameter('kelas', $searchdata['kelas']->getId());
            }
            if ($searchdata['perulangan'] != '') {
                $querybuilder->andWhere("(t.perulangan = :perulangan)");
                $querybuilder->setParameter('perulangan', $searchdata['perulangan']);

                $repetition = $searchdata['perulangan'];

                if ($searchdata['perulangan'] == 'harian') {
                    $displayresult = true;
                }
            }

            if ($searchdata['tahunAkademik'] != '' && $searchdata['kelas'] != '' && $searchdata['perulangan'] != '') {
                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container, $sekolah,
                        $searchdata['tahunAkademik']->getId(), $searchdata['kelas']->getId(),
                        $searchdata['perulangan'], $this->getRequest()->getRequestUri());
            } else {
                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container, $sekolah, null,
                        null, null, $this->getRequest()->getRequestUri());
            }

            $data = array(
                    'tahunAkademik' => $searchdata['tahunAkademik'], 'kelas' => $searchdata['kelas'],
                    'perulangan' => $searchdata['perulangan'],
            );

            if ($searchdata['perulangan'] == 'mingguan' && array_key_exists('mingguanHariKe', $searchdata)) {
                $querybuilder->andWhere("(t.mingguanHariKe = :mingguanHariKe)");
                $querybuilder->setParameter('mingguanHariKe', $searchdata['mingguanHariKe']);
                $data['mingguanHariKe'] = $searchdata['mingguanHariKe'];
                $displayresult = true;

                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container, $sekolah,
                        $searchdata['tahunAkademik']->getId(), $searchdata['kelas']->getId(),
                        $searchdata['perulangan'], $this->getRequest()->getRequestUri(),
                        $searchdata['mingguanHariKe']);
            }

            if ($searchdata['perulangan'] == 'bulanan' && array_key_exists('bulananHariKe', $searchdata)) {
                $querybuilder->andWhere("(t.bulananHariKe = :bulananHariKe)");
                $querybuilder->setParameter('bulananHariKe', $searchdata['bulananHariKe']);
                $data['bulananHariKe'] = $searchdata['bulananHariKe'];
                $displayresult = true;

                $duplicatetype = new JadwalKehadiranKepulanganDuplicateType($this->container, $sekolah,
                        $searchdata['tahunAkademik']->getId(), $searchdata['kelas']->getId(),
                        $searchdata['perulangan'], $this->getRequest()->getRequestUri(), null,
                        $searchdata['bulananHariKe']);
            }

            $commandtype = new JadwalKehadiranKepulanganCommandType($this->getRequest()->getRequestUri());
            $populatecommandform = $this->createForm($commandtype);
            $duplicateform = $this->createForm($duplicatetype);
        }

        // recreate form
        $searchform = $this
                ->createForm(
                        new JadwalKehadiranKepulanganSearchType($this->container, $sekolah,
                                $searchdata['perulangan']), $data);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($querybuilder, $this->getRequest()->query->get('page', 1));

        $sekolahlist = new SekolahList($this->container);
        $sekolahKehadiranList = $sekolahlist->buildSekolahList();

        return array(
                'pagination' => $pagination, 'schools' => $sekolahKehadiranList, 'sekolah' => $sekolah,
                'searchform' => $searchform->createView(), 'repetition' => $repetition,
                'displayresult' => $displayresult, 'searchdata' => $data,
                'populatecommandform' => $populatecommandform->createView(),
                'duplicateform' => $duplicateform->createView(), 'daynames' => $this->buildDayNames()
        );
    }

    /**
     * Finds and displays a JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/{id}/show", name="presence_schedule_show", requirements={"sekolah"="\d+"})
     * @Template()
     */
    public function showAction($sekolah, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'delete_form' => $deleteForm->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah),
                'daynames' => $this->buildDayNames()
        );
    }

    /**
     * Displays a form to create a new JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/new/", name="presence_schedule_new", requirements={"sekolah"="\d+"})
     * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function newAction($sekolah) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = new JadwalKehadiranKepulangan();
        $form = $this->createForm(new JadwalKehadiranKepulanganType($this->container, $sekolah), $entity);

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah)
        );
    }

    /**
     * Creates a new JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/create", name="presence_schedule_create", requirements={"sekolah"="\d+"})
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiranKepulangan:new.html.twig")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function createAction(Request $request, $sekolah) {
        $this->setCurrentMenu();

        $entity = new JadwalKehadiranKepulangan();
        $form = $this->createForm(new JadwalKehadiranKepulanganType($this->container, $sekolah), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.schedule.inserted'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_schedule_show',
                                            array(
                                                'sekolah' => $sekolah, 'id' => $entity->getId()
                                            )));
        }

        return array(
                'entity' => $entity, 'form' => $form->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah)
        );
    }

    /**
     * Displays a form to edit an existing JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/{id}/edit", name="presence_schedule_edit", requirements={"sekolah"="\d+"})
     * @Template()
     */
    public function editAction($sekolah, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $editForm = $this->createForm(new JadwalKehadiranKepulanganType($this->container, $sekolah), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah)
        );
    }

    /**
     * Edits an existing JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/{id}/update", name="presence_schedule_update", requirements={"sekolah"="\d+"})
     * @Method("POST")
     * @Template("FastSisdikBundle:JadwalKehadiranKepulangan:edit.html.twig")
     */
    public function updateAction(Request $request, $sekolah, $id) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new JadwalKehadiranKepulanganType($this->container, $sekolah), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()
                    ->add('success', $this->get('translator')->trans('flash.presence.schedule.updated'));

            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('presence_schedule_edit',
                                            array(
                                                'sekolah' => $sekolah, 'id' => $id
                                            )));
        }

        return array(
                'entity' => $entity, 'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
                'sekolah' => $em->getRepository('FastSisdikBundle:Sekolah')->find($sekolah)
        );
    }

    /**
     * Deletes a JadwalKehadiranKepulangan entity.
     *
     * @Route("/{sekolah}/{id}/delete", name="presence_schedule_delete", requirements={"sekolah"="\d+"})
     * @Method("POST")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteAction(Request $request, $sekolah, $id) {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FastSisdikBundle:JadwalKehadiranKepulangan')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Entity JadwalKehadiranKepulangan tak ditemukan.');
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
                                ->generateUrl('presence_schedule_list',
                                        array(
                                            'sekolah' => $sekolah, 'page' => $this->getRequest()->get('page')
                                        )));
    }

    /**
     * Duplicate schedule
     *
     * @Route("/{sekolah}/duplicateschedule", name="presence_schedule_duplicate", requirements={"sekolah"="\d+"})
     * @Method("POST")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function duplicateSchedule(Request $request, $sekolah) {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new JadwalKehadiranKepulanganDuplicateType($this->container, $sekolah));

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->leftJoin('t.tahunAkademik', 't1')
                ->leftJoin('t.kelas', 't2')->leftJoin('t.statusKehadiranKepulangan', 't3')
                ->leftJoin('t.templatesms', 't4')->where('t1.sekolah = :sekolah')
                ->addOrderBy('t3.nama', 'ASC');

        $querybuilder->setParameter('sekolah', $sekolah);

        $form->bind($request);

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
                $querybuilder->andWhere('t1.id = :tahunAkademik');
                $querybuilder->setParameter('tahunAkademik', $tahunAkademikSrc);
            }
            if ($kelasSrc != '') {
                $querybuilder->andWhere('t2.id = :kelas');
                $querybuilder->setParameter('kelas', $kelasSrc);
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

                $entity->setTahunAkademik($tahunAkademik);
                $entity->setKelas($kelas);
                $entity->setPerulangan($perulangan);
                if ($perulangan == 'mingguan')
                    $entity->setMingguanHariKe($mingguanHariKe);
                if ($perulangan == 'bulanan')
                    $entity->setBulananHariKe($bulananHariKe);

                $entity->setCommandJadwal($result->getCommandJadwal());
                $entity->setCommandMassal($result->getCommandMassal());
                $entity->setCommandRealtime($result->getCommandRealtime());
                $entity->setStatusKehadiranKepulangan($result->getStatusKehadiranKepulangan());
                $entity->setTemplatesms($result->getTemplatesms());

                $entity->setParamstatusDariJam($result->getParamstatusDariJam());
                $entity->setParamstatusHinggaJam($result->getParamstatusHinggaJam());

                $entity->setDariJam($result->getDariJam());
                $entity->setHinggaJam($result->getHinggaJam());

                $entity->setSmsMassalJam($result->getSmsMassalJam());
                $entity->setSmsRealtimeDariJam($result->getSmsRealtimeDariJam());
                $entity->setSmsRealtimeHinggaJam($result->getSmsRealtimeHinggaJam());

                $entity->setKirimSmsMassal($result->getKirimSmsMassal());
                $entity->setKirimSmsRealtime($result->getKirimSmsRealtime());

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
     * Update commandtab
     * All entities in JadwalKehadiranKepulangan are inserted
     *
     * @Route("/populatecommand", name="presence_schedule_populatecommand")
     * @Method("POST")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function populateCommandAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new JadwalKehadiranKepulanganCommandType());
        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $requestUri = $data['requestUri'];

            // remove all previous file
            $files = array(
                    $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::FP_SCHEDULE_FILE,
                    $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR
                            . self::SMS_SCHEDULE_REALTIME_FILE,
                    $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::SMS_SCHEDULE_MASSIVE_FILE
            );
            $fs = new Filesystem();
            try {
                $fs->remove($files);
            } catch (IOException $e) {
                $message = $this->get('translator')->trans('errorinfo.cannot.remove.previousfile');
                return $this->redirect($requestUri);
            }

            $retval = false;
            if ($this->updateFpCommand() && $this->updateSmsMassiveCommand()
                    && $this->updateSmsRealtimeCommand()) {
                $retval = true;
            }

            if ($retval) {
                // update commandtab

                $files = $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::SCHEDULE_PREFIX
                        . '-*';
                $allschedule = $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::SCHEDULE_FILE;

                exec("cat $files > $allschedule");

                // exec("commandtab -r");
                // exec("commandtab $allschedule");

                return $this->redirect($requestUri);
            }
        }

        $this->get('session')->getFlashBag()
                ->add('error', $this->get('translator')->trans('flash.presence.schedule.commandupdate.fail'));

        return $this->redirect($requestUri);
    }

    /**
     * Update fingerprint related schedule
     *
     */
    private function updateFpCommand() {

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->leftJoin('t.tahunAkademik', 't1')
                ->where('t1.aktif = :aktif')->setParameter('aktif', 1);
        $entities = $querybuilder->getQuery()->getResult();

        $filename = $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::FP_SCHEDULE_FILE;

        $entries = '';
        foreach ($entities as $result) {
            $idjadwalkehadirankepulangan = $result->getId();
            $perulangan = $result->getPerulangan();
            $mingguanHariKe = $result->getMingguanHariKe();
            $bulananHariKe = $result->getBulananHariKe();
            $command = $result->getCommandJadwal();

            $from = preg_replace("/:/", '.', $result->getDariJam());
            $fromHour = substr($from, 0, strrpos($from, '.'));
            $fromMinute = substr($from, strrpos($from, '.') + 1);

            $to = preg_replace("/:/", '.', $result->getHinggaJam());
            $toHour = substr($to, 0, strrpos($to, '.'));
            $toMinute = substr($to, strrpos($to, '.') + 1);

            // dari - hingga
            $diff = intval($toHour) - intval($fromHour);
            $interval = self::INTERVAL;
            if ($diff >= 1) {
                for ($i = 1; $i < $diff; $i++) {
                    $entries .= "*/$interval" . ' ' . (intval($fromHour) + $i)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                }
                if ($fromHour && $fromMinute) {
                    $entries .= (intval($fromMinute) < 59 ? intval($fromMinute) . "-59/$interval"
                            : intval($fromMinute)) . ' ' . intval($fromHour)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                    ;
                }
                if ($toHour && $toMinute) {
                    $entries .= (intval($toMinute) > 0 ? '0-' . intval($toMinute) . "/$interval"
                            : intval($toMinute)) . ' ' . intval($toHour)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                }
            } else {
                if ($fromHour && $fromMinute) {
                    $entries .= intval($fromMinute) . ' ' . intval($fromHour)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                }
                if ($toHour && $toMinute) {
                    $entries .= intval($toMinute) . ' ' . intval($toHour)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                }
            }

            // save to and overwrite schedule file
            if (!$handle = fopen($filename, 'w')) {
                $message = $this->get('translator')
                        ->trans('errorinfo.cannot.open',
                                array(
                                    '%filename%' => $filename
                                ));
                throw new \Exception($message);
            }

            if (fwrite($handle, $entries) === FALSE) {
                $message = $this->get('translator')
                        ->trans('errorinfo.cannot.write',
                                array(
                                    '%filename%' => $filename
                                ));
                throw new \Exception($message);
            }

            fclose($handle);

            $this->get('session')->getFlashBag()
                    ->add('success',
                            $this->get('translator')->trans('flash.presence.schedule.commandupdate.success'));
        }
        return true;

    }

    /**
     * Update sms realtime schedule
     *
     */
    private function updateSmsRealtimeCommand() {

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->leftJoin('t.tahunAkademik', 't1')
                ->where('t1.aktif = :aktif')->setParameter('aktif', 1);
        $entities = $querybuilder->getQuery()->getResult();

        $filename = $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR
                . self::SMS_SCHEDULE_REALTIME_FILE;

        $entries = '';
        foreach ($entities as $result) {
            if ($result->getKirimSmsRealtime()) {
                $idjadwalkehadirankepulangan = $result->getId();
                $perulangan = $result->getPerulangan();
                $mingguanHariKe = $result->getMingguanHariKe();
                $bulananHariKe = $result->getBulananHariKe();
                $command = $result->getCommandRealtime();

                $from = preg_replace("/:/", '.', $result->getSmsRealtimeDariJam());
                $fromHour = substr($from, 0, strrpos($from, '.'));
                $fromMinute = substr($from, strrpos($from, '.') + 1);

                $to = preg_replace("/:/", '.', $result->getSmsRealtimeHinggaJam());
                $toHour = substr($to, 0, strrpos($to, '.'));
                $toMinute = substr($to, strrpos($to, '.') + 1);

                $diff = intval($toHour) - intval($fromHour);
                $interval = self::INTERVAL;
                if ($diff >= 1) {
                    for ($i = 1; $i < $diff; $i++) {
                        $entries .= "*/$interval" . ' ' . (intval($fromHour) + $i)
                                . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                                . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                                . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                                . " " . self::COMMAND_PARAMETER . "\n";
                    }
                    if ($fromHour && $fromMinute) {
                        $entries .= (intval($fromMinute) < 59 ? intval($fromMinute) . "-59/$interval"
                                : intval($fromMinute)) . ' ' . intval($fromHour)
                                . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                                . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                                . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                                . " " . self::COMMAND_PARAMETER . "\n";
                    }
                    if ($toHour && $toMinute) {
                        $entries .= (intval($toMinute) > 0 ? '0-' . intval($toMinute) . "/$interval"
                                : intval($toMinute)) . ' ' . intval($toHour)
                                . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                                . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                                . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                                . " " . self::COMMAND_PARAMETER . "\n";
                    }
                } else {
                    if ($fromHour && $fromMinute) {
                        $entries .= intval($fromMinute) . ' ' . intval($fromHour)
                                . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                                . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                                . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                                . " " . self::COMMAND_PARAMETER . "\n";
                    }
                    if ($toHour && $toMinute) {
                        $entries .= intval($toMinute) . ' ' . intval($toHour)
                                . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                                . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                                . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                                . " " . self::COMMAND_PARAMETER . "\n";
                    }
                }

                // save to and overwrite schedule file
                if (!$handle = fopen($filename, 'w')) {
                    $message = $this->get('translator')
                            ->trans('errorinfo.cannot.open',
                                    array(
                                        '%filename%' => $filename
                                    ));
                    throw new \Exception($message);
                }

                if (fwrite($handle, $entries) === FALSE) {
                    $message = $this->get('translator')
                            ->trans('errorinfo.cannot.write',
                                    array(
                                        '%filename%' => $filename
                                    ));
                    throw new \Exception($message);
                }

                fclose($handle);

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.presence.schedule.commandupdate.success'));
            }
        }
        return true;

    }

    /**
     * Update sms massive schedule
     *
     */
    private function updateSmsMassiveCommand() {

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()->select('t')
                ->from('FastSisdikBundle:JadwalKehadiranKepulangan', 't')->leftJoin('t.tahunAkademik', 't1')
                ->where('t1.aktif = :aktif')->setParameter('aktif', 1);
        $entities = $querybuilder->getQuery()->getResult();

        $filename = $this->get('kernel')->getRootDir() . self::SCHEDULE_DIR . self::SMS_SCHEDULE_MASSIVE_FILE;

        $entries = '';
        foreach ($entities as $result) {
            if ($result->getKirimSmsMassal()) {
                $idjadwalkehadirankepulangan = $result->getId();
                $perulangan = $result->getPerulangan();
                $mingguanHariKe = $result->getMingguanHariKe();
                $bulananHariKe = $result->getBulananHariKe();
                $command = $result->getCommandMassal();

                $from = preg_replace("/:/", '.', $result->getSmsMassalJam());
                $fromHour = substr($from, 0, strrpos($from, '.'));
                $fromMinute = substr($from, strrpos($from, '.') + 1);

                if ($fromHour && $fromMinute) {
                    $entries .= intval($fromMinute) . ' ' . intval($fromHour)
                            . ($perulangan == 'bulanan' ? " $bulananHariKe" : ' *') . ' *'
                            . ($perulangan == 'mingguan' ? " $mingguanHariKe" : ' *')
                            . " sf2 $command --idjadwalkehadirankepulangan=$idjadwalkehadirankepulangan"
                            . " " . self::COMMAND_PARAMETER . "\n";
                }

                // save to and overwrite schedule file
                if (!$handle = fopen($filename, 'w')) {
                    $message = $this->get('translator')
                            ->trans('errorinfo.cannot.open',
                                    array(
                                        '%filename%' => $filename
                                    ));
                    throw new \Exception($message);
                }

                if (fwrite($handle, $entries) === FALSE) {
                    $message = $this->get('translator')
                            ->trans('errorinfo.cannot.write',
                                    array(
                                        '%filename%' => $filename
                                    ));
                    throw new \Exception($message);
                }

                fclose($handle);

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.presence.schedule.commandupdate.success'));
            }
        }
        return true;
    }

    private function createDeleteForm($id) {
        return $this->createFormBuilder(array(
                    'id' => $id
                ))->add('id', 'hidden')->getForm();
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.pengaturan.sisdik']['links.presenceschedule']->setCurrent(true);
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
