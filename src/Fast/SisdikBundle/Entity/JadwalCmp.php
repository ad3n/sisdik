<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JadwalCmp
 *
 * @ORM\Table(name="jadwal_cmp")
 * @ORM\Entity
 */
class JadwalCmp
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
     * @ORM\Column(name="tanggal", type="datetime", nullable=true)
     */
    private $tanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="jam", type="string", length=100, nullable=true)
     */
    private $jam;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guru_id", referencedColumnName="id")
     * })
     */
    private $guru;

    /**
     * @var \CukilMp
     *
     * @ORM\ManyToOne(targetEntity="CukilMp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cukil_mp_id", referencedColumnName="id")
     * })
     */
    private $cukilMp;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id")
     * })
     */
    private $kelas;



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
     * Set guru
     *
     * @param \Fast\SisdikBundle\Entity\Guru $guru
     * @return JadwalCmp
     */
    public function setGuru(\Fast\SisdikBundle\Entity\Guru $guru = null)
    {
        $this->guru = $guru;
    
        return $this;
    }

    /**
     * Get guru
     *
     * @return \Fast\SisdikBundle\Entity\Guru 
     */
    public function getGuru()
    {
        return $this->guru;
    }

    /**
     * Set cukilMp
     *
     * @param \Fast\SisdikBundle\Entity\CukilMp $cukilMp
     * @return JadwalCmp
     */
    public function setCukilMp(\Fast\SisdikBundle\Entity\CukilMp $cukilMp = null)
    {
        $this->cukilMp = $cukilMp;
    
        return $this;
    }

    /**
     * Get cukilMp
     *
     * @return \Fast\SisdikBundle\Entity\CukilMp 
     */
    public function getCukilMp()
    {
        return $this->cukilMp;
    }

    /**
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return JadwalCmp
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    
        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getKelas()
    {
        return $this->kelas;
    }
}