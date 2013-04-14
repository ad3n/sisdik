<?php

namespace Fast\SisdikBundle\Util;
use Fast\SisdikBundle\Entity\LogsmsKeluar;

/**
 * Short message sender -- the Messenger
 *
 * @author Ihsan Faisal
 *
 */
class Messenger
{
    private $entityManager;

    private $host;
    private $port;
    private $user;
    private $password;

    private $phonenumber;
    private $message;

    private $logid = 0;
    private $deliveryReportURL;

    public function __construct($entityManager, $host, $port, $user, $password) {
        $this->entityManager = $entityManager;

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
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
     * @return integer
     */
    public function setLogEntry() {
        $log = new LogsmsKeluar();
        $log->setKe($this->phonenumber);
        $log->setTeks($this->message);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $this->logid = $log->getId();

        return $this->logid;
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
            $this->setLogEntry();
        }
        $param = "?username=" . $this->user . "&password=" . $this->password . "&to=" . $this->phonenumber
                . "&text=" . urlencode($this->message) . "&dlr-mask=7&dlr-url="
                . urlencode($this->deliveryReportURL);
        $url = "http://" . $this->host . ":" . $this->port . "/cgi-bin/sendsms" . $param;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}

