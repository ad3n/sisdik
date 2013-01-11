<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SmsKepulanganSiswa
 *
 * @ORM\Table(name="sms_kepulangan_siswa")
 * @ORM\Entity
 */
class SmsKepulanganSiswa
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
     * @var integer
     *
     * @ORM\Column(name="sms_dlr", type="smallint", nullable=true)
     */
    private $smsDlr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sms_dlrtime", type="datetime", nullable=true)
     */
    private $smsDlrtime;



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
     * Set smsDlr
     *
     * @param integer $smsDlr
     * @return SmsKepulanganSiswa
     */
    public function setSmsDlr($smsDlr)
    {
        $this->smsDlr = $smsDlr;
    
        return $this;
    }

    /**
     * Get smsDlr
     *
     * @return integer 
     */
    public function getSmsDlr()
    {
        return $this->smsDlr;
    }

    /**
     * Set smsDlrtime
     *
     * @param \DateTime $smsDlrtime
     * @return SmsKepulanganSiswa
     */
    public function setSmsDlrtime($smsDlrtime)
    {
        $this->smsDlrtime = $smsDlrtime;
    
        return $this;
    }

    /**
     * Get smsDlrtime
     *
     * @return \DateTime 
     */
    public function getSmsDlrtime()
    {
        return $this->smsDlrtime;
    }
}