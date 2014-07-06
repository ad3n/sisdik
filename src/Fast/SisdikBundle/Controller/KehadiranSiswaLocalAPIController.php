<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Fast\SisdikBundle\Entity\KehadiranSiswa;
use Fast\SisdikBundle\Form\KehadiranSiswaType;

/**
 * KehadiranSiswa controller.
 *
 * @Route("/_local/kehadiran-siswa")
 */
class KehadiranSiswaLocalAPIController extends Controller
{
    /**
     * Edits an existing KehadiranSiswa entity.
     *
     * @Route("/{id}/update/{idlog}/{status}/{time}", name="kehadiran-siswa_update_local", defaults={"status"="","time"=""})
     */
    public function updateAction(Request $request, $id, $idlog, $status, $time) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:KehadiranSiswa')->find($id);

        if (!$entity) {
            return 'Unable to find KehadiranSiswa entity.';
        }

        $em->persist($entity);
        $em->flush();

        return 1;
    }
}
