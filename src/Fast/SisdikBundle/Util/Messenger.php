<?php

namespace Fast\SisdikBundle\Util;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Fast\SisdikBundle\Entity\LogsmsKeluar;

/**
 * Short message sender -- the Messenger
 *
 * @author Ihsan Faisal
 *
 */
class Messenger
{
    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    private $provider;
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $password;
    private $resource;
    private $apikey;
    private $report;

    private $phonenumber;
    private $message;

    private $logid = 0;
    private $deliveryReportURL;
    private $apiResult;

    public function __construct($container, $provider, $scheme, $host, $port, $user, $password, $resource,
            $apikey, $report) {
        $this->container = $container;

        $this->provider = $provider;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->resource = $resource;
        $this->apikey = $apikey;
        $this->report = $report;
    }

    /**
     * set phone number
     *
     * @param string $phonenumber
     */
    public function setPhonenumber($phonenumber) {
        $this->phonenumber = $phonenumber;
    }

    /**
     * Set message
     *
     * @param string $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }

    /**
     * Set log entry
     *
     * @param  \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return integer
     */
    public function setLogEntry(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $em = $this->container->get('doctrine')->getManager();

        $log = new LogsmsKeluar();
        $log->setPenyediaApi($this->provider);
        $log->setKe($this->phonenumber);
        $log->setTeks($this->message);
        $log->setSekolah($sekolah);
        $log->setWaktuPanggilApi(new \DateTime());

        $em->persist($log);
        $em->flush();

        $this->logid = $log->getId();

        return $this->logid;
    }

    /**
     * @param string $url
     * @param string $hasil
     */
    private function updateLogHasilAPI($url, $hasil) {
        $em = $this->container->get('doctrine')->getManager();

        /* @var $entity \Fast\SisdikBundle\Entity\LogsmsKeluar */
        $entity = $em->getRepository('FastSisdikBundle:LogsmsKeluar')->find($this->logid);
        $entity->setApiTerpanggil($url);
        $entity->setHasilAPI($hasil);

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Set delivery report controller
     *
     * @param string $url
     */
    public function setDeliveryReportURL($url) {
        $this->deliveryReportURL = $url;
    }

    /**
     * Send message
     *
     * @return integer
     */
    public function sendMessage() {
        if ($this->logid == 0) {
            $user = $this->container->get('security.context')->getToken()->getUser();
            $sekolah = $user->getSekolah();

            $this->setLogEntry($sekolah);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 13);

        if ($this->provider == "local") {

            $param = "?username=" . $this->user . "&password=" . $this->password . "&to="
                    . $this->phonenumber . "&text=" . urlencode($this->message);

            if ($this->report == "1") {
                $this
                        ->setDeliveryReportURL(
                                $this->container->get('router')
                                        ->generate("localapi_logsmskeluar_dlr_update",
                                                array(
                                                    'logid' => $this->logid, 'status' => "%d", 'time' => "%T"
                                                ), UrlGeneratorInterface::ABSOLUTE_URL));

                $param .= "&dlr-mask=7&dlr-url=" . urlencode($this->deliveryReportURL);
            }

            $url = $this->scheme . "://" . $this->host . ":" . $this->port . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);
            $this->updateLogHasilAPI($url, $hasil);

        } elseif ($this->provider == "rajasms") {

            $param = "?nohp=" . $this->phonenumber . "&pesan=" . urlencode($this->message) . "&key="
                    . $this->apikey;
            $url = $this->scheme . "://" . $this->host . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);
            $this->updateLogHasilAPI($url, $hasil);

        } elseif ($this->provider == "zenziva") {

            $param = "?userkey=" . $this->user . "&passkey=" . $this->password . "&nohp="
                    . $this->phonenumber . "&pesan=" . urlencode($this->message);
            $url = $this->scheme . "://" . $this->host . $this->resource . $param;

            curl_setopt($ch, CURLOPT_URL, $url);
            $hasil = curl_exec($ch);
            $this->updateLogHasilAPI($url, $hasil);

        }

        curl_close($ch);

    }
}
