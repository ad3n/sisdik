<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\JadwalCmp
 *
 * @ORM\Table(name="jadwal_cmp")
 * @ORM\Entity
 */
class JadwalCmp
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
     * @var \DateTime $tanggal
     *
     * @ORM\Column(name="tanggal", type="datetime", nullable=true)
     */
    private $tanggal;

    /**
     * @var string $jam
     *
     * @ORM\Column(name="jam", type="string", length=100, nullable=true)
     */
    private $jam;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idguru", referencedColumnName="id")
     * })
     */
    private $idguru;

    /**
     * @var CukilMp
     *
     * @ORM\ManyToOne(targetEntity="CukilMp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idcukil_mp", referencedColumnName="id")
     * })
     */
    private $idcukilMp;

    /**
     * @var Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;



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
     * @return JadwalCmp
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
     * Set jam
     *
     * @param string $jam
     * @return JadwalCmp
     */
    public function setJam($jam)
    {
        $this->jam = $jam;
    
        return $this;
    }

    /**
     * Get jam
     *
     * @return string 
     */
    public function getJam()
    {
        return $this->jam;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return JadwalCmp
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
     * Set idguru
     *
     * @param Fast\SisdikBundle\Entity\Guru $idguru
     * @return JadwalCmp
     */
    public function setIdguru(\Fast\SisdikBundle\Entity\Guru $idguru = null)
    {
        $this->idguru = $idguru;
    
        return $this;
    }

    /**
     * Get idguru
     *
     * @return Fast\SisdikBundle\Entity\Guru 
     */
    public function getIdguru()
    {
        return $this->idguru;
    }

    /**
     * Set idcukilMp
     *
     * @param Fast\SisdikBundle\Entity\CukilMp $idcukilMp
     * @return JadwalCmp
     */
    public function setIdcukilMp(\Fast\SisdikBundle\Entity\CukilMp $idcukilMp = null)
    {
        $this->idcukilMp = $idcukilMp;
    
        return $this;
    }

    /**
     * Get idcukilMp
     *
     * @return Fast\SisdikBundle\Entity\CukilMp 
     */
    public function getIdcukilMp()
    {
        return $this->idcukilMp;
    }

    /**
     * Set idkelas
     *
     * @param Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return JadwalCmp
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null)
    {
        $this->idkelas = $idkelas;
    
        return $this;
    }

    /**
     * Get idkelas
     *
     * @return Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas()
    {
        return $this->idkelas;
    }
}