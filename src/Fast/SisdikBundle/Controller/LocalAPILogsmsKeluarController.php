<?php

namespace Fast\SisdikBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * LocalAPI LogsmsKeluar controller.
 *
 * @Route("/_local/logsmskeluar")
 */
class LocalAPILogsmsKeluarController extends Controller
{

    /**
     * Update delivery report (LogsmsKeluar entity)
     *
     * @Route("/dlr/{logid}/update/{status}/{time}", name="localapi_logsmskeluar_dlr_update")
     */
    public function updateAction(Request $request, $logid, $status, $time) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FastSisdikBundle:LogsmsKeluar')->find($logid);

        if (!$entity) {
            throw $this->createNotFoundException('Entity LogsmsKeluar tak ditemukan.');
        }

        $entity->setDlr($status);
        $entity->setDlrtime(new \DateTime(date("Y-m-d H:i:s", $time)));

        $em->persist($entity);
        $em->flush();

        $response = new Response("success", 200);

        return $response;
    }
}
