<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="logfp")
 * @ORM\Entity
 */
class Logfp
{
    /**
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="pin", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $pin;

    /**
     * @ORM\Column(name="datetime", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $datetime;

    /**
     * @ORM\Column(name="status", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $status;

    /**
     * @ORM\Column(name="ip", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $ip;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $pin
     */
    public function setPin($pin)
    {
        $this->pin = $pin;
    }

    /**
     * @return string
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}
