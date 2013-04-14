<?php

namespace Fast\SisdikBundle\Entity;
use Symfony\Component\Config\Definition\IntegerNode;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogsmsKeluar
 *
 * @ORM\Table(name="logsms_keluar")
 * @ORM\Entity
 */
class LogsmsKeluar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ke", type="string", length=50, nullable=true)
     */
    private $ke;

    /**
     * @var string
     *
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     */
    private $teks;

    /**
     * @var integer
     *
     * @ORM\Column(name="dlr", type="smallint", nullable=true)
     */
    private $dlr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dlrtime", type="datetime", nullable=true)
     */
    private $dlrtime;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set ke
     *
     * @param string $ke
     * @return LogsmsKeluar
     */
    public function setKe($ke) {
        $this->ke = $ke;

        return $this;
    }

    /**
     * Get ke
     *
     * @return string
     */
    public function getKe() {
        return $this->ke;
    }

    /**
     * Set teks
     *
     * @param string $teks
     * @return LogsmsKeluar
     */
    public function setTeks($teks) {
        $this->teks = $teks;

        return $this;
    }

    /**
     * Get teks
     *
     * @return string
     */
    public function getTeks() {
        return $this->teks;
    }

    /**
     * Set dlr
     *
     * @param integer $dlr
     * @return LogsmsKeluar
     */
    public function setDlr($dlr) {
        $this->dlr = $dlr;

        return $this;
    }

    /**
     * Get dlr
     *
     * @return integer
     */
    public function getDlr() {
        return $this->dlr;
    }

    /**
     * Set dlrtime
     *
     * @param \DateTime $dlrtime
     * @return LogsmsKeluar
     */
    public function setDlrtime($dlrtime) {
        $this->dlrtime = $dlrtime;

        return $this;
    }

    /**
     * Get dlrtime
     *
     * @return \DateTime
     */
    public function getDlrtime() {
        return $this->dlrtime;
    }
}
