<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KehadiranGuru
 *
 * @ORM\Table(name="kehadiran_guru")
 * @ORM\Entity
 */
class KehadiranGuru
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
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     */
    private $tanggal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hadir", type="boolean", nullable=true)
     */
    private $hadir;



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
     * Set tanggal
     *
     * @param \DateTime $tanggal
     * @return KehadiranGuru
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    
        return $this;
    }

    /**
     * Get tanggal
     *
     * @return \DateTime 
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * Set hadir
     *
     * @param boolean $hadir
     * @return KehadiranGuru
     */
    public function setHadir($hadir)
    {
        $this->hadir = $hadir;
    
        return $this;
    }

    /**
     * Get hadir
     *
     * @return boolean 
     */
    public function getHadir()
    {
        return $this->hadir;
    }
}