<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\SmsKepulanganSiswa
 *
 * @ORM\Table(name="sms_kepulangan_siswa")
 * @ORM\Entity
 */
class SmsKepulanganSiswa
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
     * @var integer $smsDlr
     *
     * @ORM\Column(name="sms_dlr", type="smallint", nullable=true)
     */
    private $smsDlr;

    /**
     * @var \DateTime $smsDlrtime
     *
     * @ORM\Column(name="sms_dlrtime", type="datetime", nullable=true)
     */
    private $smsDlrtime;

    /**
     * @var KepulanganSiswa
     *
     * @ORM\ManyToOne(targetEntity="KepulanganSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkepulangan_siswa", referencedColumnName="id")
     * })
     */
    private $idkepulanganSiswa;



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

    /**
     * Set idkepulanganSiswa
     *
     * @param Fast\SisdikBundle\Entity\KepulanganSiswa $idkepulanganSiswa
     * @return SmsKepulanganSiswa
     */
    public function setIdkepulanganSiswa(\Fast\SisdikBundle\Entity\KepulanganSiswa $idkepulanganSiswa = null)
    {
        $this->idkepulanganSiswa = $idkepulanganSiswa;
    
        return $this;
    }

    /**
     * Get idkepulanganSiswa
     *
     * @return Fast\SisdikBundle\Entity\KepulanganSiswa 
     */
    public function getIdkepulanganSiswa()
    {
        return $this->idkepulanganSiswa;
    }
}