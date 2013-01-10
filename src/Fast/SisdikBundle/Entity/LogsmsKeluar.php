<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\LogsmsKeluar
 *
 * @ORM\Table(name="logsms_keluar")
 * @ORM\Entity
 */
class LogsmsKeluar
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $ke
     *
     * @ORM\Column(name="ke", type="string", length=50, nullable=true)
     */
    private $ke;

    /**
     * @var string $teks
     *
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     */
    private $teks;

    /**
     * @var boolean $dlr
     *
     * @ORM\Column(name="dlr", type="boolean", nullable=true)
     */
    private $dlr;

    /**
     * @var \DateTime $dlrtime
     *
     * @ORM\Column(name="dlrtime", type="datetime", nullable=true)
     */
    private $dlrtime;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ke
     *
     * @param string $ke
     * @return LogsmsKeluar
     */
    public function setKe($ke)
    {
        $this->ke = $ke;
    
        return $this;
    }

    /**
     * Get ke
     *
     * @return string 
     */
    public function getKe()
    {
        return $this->ke;
    }

    /**
     * Set teks
     *
     * @param string $teks
     * @return LogsmsKeluar
     */
    public function setTeks($teks)
    {
        $this->teks = $teks;
    
        return $this;
    }

    /**
     * Get teks
     *
     * @return string 
     */
    public function getTeks()
    {
        return $this->teks;
    }

    /**
     * Set dlr
     *
     * @param boolean $dlr
     * @return LogsmsKeluar
     */
    public function setDlr($dlr)
    {
        $this->dlr = $dlr;
    
        return $this;
    }

    /**
     * Get dlr
     *
     * @return boolean 
     */
    public function getDlr()
    {
        return $this->dlr;
    }

    /**
     * Set dlrtime
     *
     * @param \DateTime $dlrtime
     * @return LogsmsKeluar
     */
    public function setDlrtime($dlrtime)
    {
        $this->dlrtime = $dlrtime;
    
        return $this;
    }

    /**
     * Get dlrtime
     *
     * @return \DateTime 
     */
    public function getDlrtime()
    {
        return $this->dlrtime;
    }
}