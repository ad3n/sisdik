<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KalenderPendidikan
 *
 * @ORM\Table(name="kalender_pendidikan")
 * @ORM\Entity
 */
class KalenderPendidikan
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
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
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var boolean
     *
     * @ORM\Column(name="kbm", type="boolean", nullable=false)
     */
    private $kbm;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;



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
     * @return KalenderPendidikan
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
     * Set keterangan
     *
     * @param string $keterangan
     * @return KalenderPendidikan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    
        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string 
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * Set kbm
     *
     * @param boolean $kbm
     * @return KalenderPendidikan
     */
    public function setKbm($kbm)
    {
        $this->kbm = $kbm;
    
        return $this;
    }

    /**
     * Get kbm
     *
     * @return boolean 
     */
    public function getKbm()
    {
        return $this->kbm;
    }

    /**
     * Set idsekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return KalenderPendidikan
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null)
    {
        $this->idsekolah = $idsekolah;
    
        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }
}