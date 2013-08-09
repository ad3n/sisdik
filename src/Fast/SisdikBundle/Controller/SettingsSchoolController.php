<?php

namespace Fast\SisdikBundle\Controller;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Fast\SisdikBundle\Entity\Sekolah;
use Fast\SisdikBundle\Form\SekolahFormType;
use Fast\SisdikBundle\Form\SimpleSearchFormType;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 *
 * @author Ihsan Faisal ihsan
 * @Route("/school")
 */
class SettingsSchoolController extends Controller
{
    /**
     * @Template()
     * @Route("/", name="settings_school_list")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function listAction(Request $request) {
        $this->setCurrentMenu();

        $searchcondition = '';

        $searchform = $this->createForm(new SimpleSearchFormType());

        $searchform->submit($request);
        $searchdata = $searchform->getData();
        if ($searchdata['searchkey'] != '') {
            $searchcondition = "( s.nama LIKE '%{$searchdata['searchkey']}%' OR s.kode LIKE '%{$searchdata['searchkey']}%' )";
        }

        $em = $this->getDoctrine()->getManager();
        $query = $em
                ->createQuery(
                        "SELECT s FROM FastSisdikBundle:Sekolah s "
                                . ($searchcondition != '' ? " WHERE $searchcondition " : '')
                                . ' ORDER BY s.nama ASC');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $this->getRequest()->query->get('page', 1));

        return array(
            'pagination' => $pagination, 'form' => $searchform->createView(),
        );
    }

    /**
     * @Template()
     * @Route("/add", name="settings_school_add")
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function addAction(Request $request) {
        $this->setCurrentMenu();

        $sekolah = new Sekolah();
        $form = $this->createForm(new SekolahFormType(), $sekolah);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                // $sekolah = $form->getData();

                $em = $this->getDoctrine()->getManager();

                $qbe = $em->createQueryBuilder();
                $querynomor = $em->createQueryBuilder()->select($qbe->expr()->max('sekolah.nomorUrut'))
                        ->from('FastSisdikBundle:Sekolah', 'sekolah');

                $nomorUrut = $querynomor->getQuery()->getSingleScalarResult();
                $nomorUrut = $nomorUrut === null ? 0 : $nomorUrut;
                $nomorUrut++;
                $sekolah->setNomorUrut($nomorUrut);

                $em->persist($sekolah);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.settings.school.inserted',
                                                array(
                                                    '%schoolname%' => $sekolah->getNama()
                                                )));

                return $this->redirect($this->generateUrl('settings_school_list'));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Template()
     * @Route("/edit/{id}/{page}", name="settings_school_edit", defaults={"page"=1})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function editAction(Request $request, $id, $page) {
        $this->setCurrentMenu();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('FastSisdikBundle:Sekolah');
        $school = $repository->find($id);

        $form = $this->createForm(new SekolahFormType(), $school);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $school = $form->getData();
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.settings.school.updated',
                                                array(
                                                    '%schoolname%' => $school->getNama()
                                                )));

                return $this
                        ->redirect(
                                $this
                                        ->generateUrl('settings_school_list',
                                                array(
                                                    'page' => $page
                                                )));
            }
        }

        return array(
            'form' => $form->createView(), 'id' => $id, 'page' => $page
        );
    }

    /**
     * @Route("/delete/{id}/{confirmed}", name="settings_school_delete", defaults={"confirmed"=0}, requirements={"id"="\d+"})
     * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function deleteAction($id, $confirmed) {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('FastSisdikBundle:Sekolah');
        $school = $repository->find($id);
        $schoolname = $school->getNama();

        if ($confirmed == 1) {
            try {
                $em->remove($school);
                $em->flush();

                $this->get('session')->getFlashBag()
                        ->add('success',
                                $this->get('translator')
                                        ->trans('flash.settings.school.deleted',
                                                array(
                                                    '%schoolname%' => $schoolname
                                                )));

                return $this->redirect($this->generateUrl('settings_school_list'));
            } catch (DBALException $e) {
                $exception = $this->get('translator')->trans('exception.delete.restrict');
                throw new DBALException($exception);
            }
        }

        $this->get('session')->getFlashBag()
                ->add('error',
                        $this->get('translator')
                                ->trans('flash.settings.school.fail.delete',
                                        array(
                                            '%schoolname%' => $schoolname
                                        )));

        return $this->redirect($this->generateUrl('settings_school_list'));
    }

    private function setCurrentMenu() {
        $menu = $this->get('fast_sisdik.menu.main');
        $menu['headings.pengaturan.sisdik']['links.schools']->setCurrent(true);
    }
}
