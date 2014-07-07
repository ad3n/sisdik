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
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Util\Calendar;
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
        $sekolah = $this->isRegisteredToSchool();
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
        $sekolah = $this->isRegisteredToSchool();

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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $searchform = $this->createForm('sisdik_kalenderpendidikansearch', [
            'year' => $year,
            'month' => $month,
        ]);

        $nextmonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));

        $em = $this->getDoctrine()->getManager();

        $querybuilder = $em->createQueryBuilder()
            ->select('kalenderPendidikan')
            ->from('FastSisdikBundle:KalenderPendidikan', 'kalenderPendidikan')
            ->where('kalenderPendidikan.sekolah = :sekolah')
            ->andWhere('kalenderPendidikan.tanggal >= :firstday AND kalenderPendidikan.tanggal < :nextmonth')
            ->setParameter('sekolah', $sekolah)
            ->setParameter('firstday', "$year-$month-01")
            ->setParameter('nextmonth', $nextmonth)
        ;

        $dates = $querybuilder->getQuery()->getResult();
        $activedates = array();
        if (!empty($dates)) {
            foreach ($dates as $date) {
                $activedates[$date->getTanggal()->format('j')] = $date->getTanggal()->format('j');
            }
        }

        $objectCalendar = new Calendar;
        $calendar = $objectCalendar->createMonthlyCalendar($year, $month);

        $form = $this->createForm(new KalenderPendidikanType($calendar, $activedates));

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
        $sekolah = $this->isRegisteredToSchool();
        $this->setCurrentMenu();

        $objectCalendar = new Calendar;
        $calendar = $objectCalendar->createMonthlyCalendar($year, $month);

        $form = $this->createForm(new KalenderPendidikanType($calendar));
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $data = $form->getData();
            $dates = '';

            // TODO: use a smart update method, only delete an already existing data and insert a new one

            // delete previously saved data in the selected year-month
            $nextmonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));
            $query = $em->createQuery(
                    "DELETE FastSisdikBundle:KalenderPendidikan t
                    WHERE t.tanggal >= :firstday AND t.tanggal < :nextmonth
                    AND t.sekolah = {$sekolah->getId()}"
                )
                ->setParameter('firstday', "$year-$month-01")
                ->setParameter('nextmonth', $nextmonth)
            ;
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

            $this
                ->get('session')
                ->getFlashBag()
                ->add(
                    'success',
                    $this->get('translator')->trans('flash.data.academic.calendar.updated', [
                        '%year%' => $year,
                        '%month%' => $calendar['months'][$month],
                    ])
                )
            ;
        }

        return $this->redirect($this->generateUrl('kalender-akademik_display', [
            'year' => $year,
            'month' => $month,
        ]));
    }

    private function setCurrentMenu()
    {
        $menu = $this->container->get('fast_sisdik.menu.main');
        $menu[$this->get('translator')->trans('headings.academic', array(), 'navigations')][$this->get('translator')->trans('links.data.academiccalendar', array(), 'navigations')]->setCurrent(true);
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
