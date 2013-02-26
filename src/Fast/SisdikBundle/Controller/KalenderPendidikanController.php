<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\KalenderPendidikan;
use Fast\SisdikBundle\Form\KalenderPendidikanType;
use Fast\SisdikBundle\Form\KalenderPendidikanSearchType;
use Fast\SisdikBundle\Entity\Sekolah;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * KalenderPendidikan controller.
 *
 * @Route("/data/kaldemik")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class KalenderPendidikanController extends Controller
{
    /**
     * KalenderPendidikan index
     *
     * @Route("/", name="data_kaldemik")
     * @Template()
     */
    public function indexAction() {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $searchform = $this
                ->createForm(new KalenderPendidikanSearchType(),
                        array(
                            'year' => date('Y')
                        ));

        return array(
            'searchform' => $searchform->createView()
        );
    }

    /**
     * KalenderPendidikan process
     *
     * @Route("/process", name="data_kaldemik_process")
     * @Method("POST")
     */
    public function processAction(Request $request) {
        $sekolah = $this->isRegisteredToSchool();

        $searchform = $this->createForm(new KalenderPendidikanSearchType());

        $request = $this->getRequest();
        $searchform->bind($request);

        $data = $searchform->getData();

        if ($data['year'] != '') {
            return $this
                    ->redirect(
                            $this
                                    ->generateUrl('data_kaldemik_display',
                                            array(
                                                'year' => $data['year'], 'month' => $data['month']
                                            )));
        }

        return $this->redirect($this->generateUrl('data_kaldemik'));
    }

    /**
     * Displays KalenderPendidikan entities.
     *
     * @Route("/display/{year}/{month}", name="data_kaldemik_display")
     * @Template()
     */
    public function displayAction($year, $month) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $searchform = $this
                ->createForm(new KalenderPendidikanSearchType(),
                        array(
                            'year' => $year, 'month' => $month
                        ));

        // get data for the selected year-month
        $em = $this->getDoctrine()->getManager();

        $nextmonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));
        $query = $em
                ->createQuery(
                        "SELECT t FROM FastSisdikBundle:KalenderPendidikan t
                        LEFT JOIN t.sekolah t1
                        WHERE t.tanggal >= :firstday AND t.tanggal < :nextmonth
                        AND t1.id = {$sekolah->getId()}")
                ->setParameter('firstday', "$year-$month-01")
                ->setParameter('nextmonth', $nextmonth);
        $dates = $query->getResult();
        $activedates = array();
        if (!empty($dates)) {
            foreach ($dates as $date) {
                $activedates[$date->getTanggal()->format('j')] = $date->getTanggal()->format('j');
            }
        }

        $calendar = $this->createCalendar($year, $month);

        $form = $this->createForm(new KalenderPendidikanType($calendar, $activedates));

        return array(
                'calendar' => $calendar, 'searchform' => $searchform->createView(),
                'form' => $form->createView(), $activedates
        );
    }

    /**
     * Edits an existing KalenderPendidikan entity.
     *
     * @Route("/update/{year}/{month}", name="data_kaldemik_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $year, $month) {
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $calendar = $this->createCalendar($year, $month);

        $form = $this->createForm(new KalenderPendidikanType($calendar));
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $data = $form->getData();
            $dates = '';

            // TODO: use a smart update method, only delete an already existing data and insert a new one
            // delete previously saved data in the selected year-month
            $nextmonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));
            $query = $em
                    ->createQuery(
                            "DELETE FastSisdikBundle:KalenderPendidikan t
                            WHERE t.tanggal >= :firstday AND t.tanggal < :nextmonth
                            AND t.sekolah = {$sekolah->getId()}")
                    ->setParameter('firstday', "$year-$month-01")
                    ->setParameter('nextmonth', $nextmonth);
            $query->execute();

            // insert the new data for the selected year-month
            for ($i = 1; $i <= 31; $i++) {
                if (array_key_exists('kbm_' . $i, $data) === true) {
                    if ($data['kbm_' . $i] == 1) {
                        $date = new \DateTime("$year-$month-$i");

                        $entity = new KalenderPendidikan();
                        $entity->setSekolah($sekolah);
                        $entity->setKbm(true);
                        $entity->setTanggal($date);

                        $em->persist($entity);
                    }
                }
            }
            $em->flush();

            $this->get('session')
                    ->setFlash('success',
                            $this->get('translator')
                                    ->trans('flash.data.academic.calendar.updated',
                                            array(
                                                    '%year%' => $year,
                                                    '%month%' => $calendar['months'][$month]
                                            )));
        }

        return $this
                ->redirect(
                        $this
                                ->generateUrl('data_kaldemik_display',
                                        array(
                                            'year' => $year, 'month' => $month
                                        )));
    }

    /**
     * create monthly calendar
     * 
     * @param integer $theyear
     * @param integer $themonth
     */
    private function createCalendar($theyear = NULL, $themonth = NULL) {
        $now = time();

        if (!empty($theyear)) {
            $year = $theyear;
        } else {
            $year = date('Y', $now);
        }

        if (!empty($themonth)) {
            $month = $themonth;
        } else {
            $month = date('m', $now);
        }

        /**
         * want to start on sunday? use this array AND ( important! ) set $day_offset to 0 ( zero )
         * $days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
         */
        $days = array(
            'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu', 'Minggu'
        );
        $months = array(
                1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                'September', 'Oktober', 'November', 'Desember'
        );

        // day offset, 1 is monday, 0 is sunday
        $day_offset = 1;

        $start_day = mktime(0, 0, 0, $month, 1, $year);
        $start_day_number = date('w', $start_day);
        $days_in_month = date('t', $start_day);
        $row = 0;
        $cal = array();
        $trow = 0;
        $blank_days = $start_day_number - $day_offset;

        if ($blank_days < 0) {
            $blank_days = 7 - abs($blank_days);
        }

        for ($x = 0; $x < $blank_days; $x++) {
            $cal[$row][$trow]['num'] = null;
            $trow++;
        }

        for ($x = 1; $x <= $days_in_month; $x++) {
            if (($x + $blank_days - 1) % 7 == 0) {
                $row++;
            }
            $cal[$row][$trow]['num'] = $x;
            $cal[$row][$trow]['ts'] = mktime(0, 0, 0, $month, $x, $year);
            if (($x + $blank_days) % 7 == 0) {
                $cal[$row][$trow]['off'] = 1;
            } else {
                $cal[$row][$trow]['off'] = null;
            }

            $trow++;
        }
        while ((($days_in_month + $blank_days) % 7) != 0) {
            $cal[$row][$trow]['num'] = null;
            $days_in_month++;
            $trow++;
        }

        return array(
                'months' => $months, 'days' => $days, 'cal' => $cal, 'month' => abs($month),
                'year' => $year,
        );
    }

    private function setCurrentMenu() {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu['headings.academic']['links.data.academiccalendar']->setCurrent(true);
    }

    private function isRegisteredToSchool() {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sekolah = $user->getSekolah();

        if (is_object($sekolah) && $sekolah instanceof Sekolah) {
            return $sekolah;
        } else if ($this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new AccessDeniedException($this->get('translator')->trans('exception.useadmin'));
        } else {
            throw new AccessDeniedException(
                    $this->get('translator')->trans('exception.registertoschool'));
        }
    }
}
