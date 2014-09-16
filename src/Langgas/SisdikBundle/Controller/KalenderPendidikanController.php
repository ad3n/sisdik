<?php

namespace Langgas\SisdikBundle\Controller;

use Doctrine\ORM\EntityManager;
use Langgas\SisdikBundle\Entity\KalenderPendidikan;
use Langgas\SisdikBundle\Entity\Sekolah;
use Langgas\SisdikBundle\Util\Calendar;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @Route("/kalender-akademik")
 * @PreAuthorize("hasRole('ROLE_WAKIL_KEPALA_SEKOLAH')")
 */
class KalenderPendidikanController extends Controller
{
    /**
     * @Route("/", name="kalender-akademik")
     * @Template()
     */
    public function indexAction()
    {
        $this->setCurrentMenu();

        $searchform = $this->createForm('sisdik_kalenderpendidikansearch', ['year' => date('Y')]);

        return [
            'searchform' => $searchform->createView(),
        ];
    }

    /**
     * @Route("/process", name="kalender-akademik_process")
     * @Method("POST")
     */
    public function processAction(Request $request)
    {
        $searchform = $this->createForm('sisdik_kalenderpendidikansearch');

        $request = $this->getRequest();
        $searchform->submit($request);
        $data = $searchform->getData();

        if ($data['year'] != '') {
            return $this->redirect($this->generateUrl('kalender-akademik_display', [
                'year' => $data['year'],
                'month' => $data['month'],
            ]));
        }

        return $this->redirect($this->generateUrl('kalender-akademik'));
    }

    /**
     * @Route("/display/{year}/{month}", name="kalender-akademik_display")
     * @Template()
     */
    public function displayAction($year, $month)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $searchform = $this->createForm('sisdik_kalenderpendidikansearch', [
            'year' => $year,
            'month' => $month,
        ]);

        $nextmonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));

        /* @var $em EntityManager */
        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('kalenderPendidikan')
            ->from('LanggasSisdikBundle:KalenderPendidikan', 'kalenderPendidikan')
            ->where('kalenderPendidikan.sekolah = :sekolah')
            ->andWhere('kalenderPendidikan.tanggal >= :firstday AND kalenderPendidikan.tanggal < :nextmonth')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('firstday', "$year-$month-01")
            ->setParameter('nextmonth', $nextmonth)
        ;

        $dates = $querybuilder->getQuery()->getResult();
        $activedates = [];
        if (!empty($dates)) {
            foreach ($dates as $date) {
                if ($date instanceof KalenderPendidikan) {
                    if ($date->getKbm() === true) {
                        $activedates[$date->getTanggal()->format('j')] = $date->getTanggal()->format('j');
                    }
                }
            }
        }

        $objectCalendar = new Calendar;
        $calendar = $objectCalendar->createMonthlyCalendar($year, $month);

        $form = $this->createForm('sisdik_kalenderpendidikan', null, [
            'calendar' => $calendar,
            'activedates' => $activedates,
        ]);

        return [
            'calendar' => $calendar,
            'searchform' => $searchform->createView(),
            'form' => $form->createView(),
            $activedates,
        ];
    }

    /**
     * @Route("/update/{year}/{month}", name="kalender-akademik_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $year, $month)
    {
        $sekolah = $this->getSekolah();
        $this->setCurrentMenu();

        $objectCalendar = new Calendar;
        $calendar = $objectCalendar->createMonthlyCalendar($year, $month);

        $form = $this->createForm('sisdik_kalenderpendidikan', null, [
            'calendar' => $calendar,
        ]);
        $form->submit($request);

        if ($form->isValid()) {
            /* @var $em EntityManager */
            $em = $this->getDoctrine()->getManager();
            $data = $form->getData();

            for ($i = 1; $i <= 31; $i++) {
                if (array_key_exists('kbm_' . $i, $data) === true) {
                    $tanggal = new \DateTime("$year-$month-$i");
                    $kalenderPendidikan = $em->getRepository('LanggasSisdikBundle:KalenderPendidikan')->findOneBy([
                        'sekolah' => $sekolah,
                        'tanggal' => $tanggal,
                    ]);
                    if ($kalenderPendidikan instanceof KalenderPendidikan) {
                        if ($data['kbm_' . $i] == 1) {
                            $kalenderPendidikan->setKbm(true);
                        } else {
                            $kalenderPendidikan->setKbm(false);
                        }
                        $em->persist($kalenderPendidikan);
                    } else {
                        if ($data['kbm_' . $i] == 1) {
                            $kalenderPendidikan = new KalenderPendidikan;
                            $kalenderPendidikan->setSekolah($sekolah);
                            $kalenderPendidikan->setKbm(true);
                            $kalenderPendidikan->setTanggal($tanggal);
                            $em->persist($kalenderPendidikan);
                        }
                    }
                }
            }
            $em->flush();

            $this
                ->get('session')
                ->getFlashBag()
                ->add('success', $this->get('translator')->trans('flash.data.academic.calendar.updated', [
                    '%year%' => $year,
                    '%month%' => $calendar['months'][$month],
                ]))
            ;
        }

        return $this->redirect($this->generateUrl('kalender-akademik_display', [
            'year' => $year,
            'month' => $month,
        ]));
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('langgas_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.academic', [], 'navigations')][$this->get('translator')->trans('links.data.academiccalendar', [], 'navigations')]->setCurrent(true);
    }

    /**
     * @return Sekolah
     */
    private function getSekolah()
    {
        return $this->getUser()->getSekolah();
    }
}
