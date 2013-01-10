<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KehadiranSiswa
 *
 * @ORM\Table(name="kehadiran_siswa")
 * @ORM\Entity
 */
class KehadiranSiswa
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
     * @ORM\Column(name="prioritas_pembaruan", type="smallint", nullable=false)
     */
    private $prioritasPembaruan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     */
    private $tanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="jam", type="string", length=10, nullable=true)
     */
    private $jam;

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
     * @var boolean
     *
     * @ORM\Column(name="sms_terproses", type="boolean", nullable=false)
     */
    private $smsTerproses;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan_status", type="string", length=45, nullable=true)
     */
    private $keteranganStatus;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsiswa", referencedColumnName="id")
     * })
     */
    private $idsiswa;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;

    /**
     * @var \StatusKehadiranKepulangan
     *
     * @ORM\ManyToOne(targetEntity="StatusKehadiranKepulangan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idstatus_kehadiran_kepulangan", referencedColumnName="id")
     * })
     */
    private $idstatusKehadiranKepulangan;



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
     * Set prioritasPembaruan
     *
     * @param integer $prioritasPembaruan
     * @return KehadiranSiswa
     */
    public function setPrioritasPembaruan($prioritasPembaruan)
    {
        $this->prioritasPembaruan = $prioritasPembaruan;
    
        return $this;
    }

    /**
     * Get prioritasPembaruan
     *
     * @return integer 
     */
    public function getPrioritasPembaruan()
    {
        return $this->prioritasPembaruan;
    }

    /**
     * Set tanggal
     *
     * @param \DateTime $tanggal
     * @return KehadiranSiswa
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
     * @return KehadiranSiswa
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
     * Set smsDlr
     *
     * @param integer $smsDlr
     * @return KehadiranSiswa
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
     * @return KehadiranSiswa
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
     * Set smsTerproses
     *
     * @param boolean $smsTerproses
     * @return KehadiranSiswa
     */
    public function setSmsTerproses($smsTerproses)
    {
        $this->smsTerproses = $smsTerproses;
    
        return $this;
    }

    /**
     * Get smsTerproses
     *
     * @return boolean 
     */
    public function getSmsTerproses()
    {
        return $this->smsTerproses;
    }

    /**
     * Set keteranganStatus
     *
     * @param string $keteranganStatus
     * @return KehadiranSiswa
     */
    public function setKeteranganStatus($keteranganStatus)
    {
        $this->keteranganStatus = $keteranganStatus;
    
        return $this;
    }

    /**
     * Get keteranganStatus
     *
     * @return string 
     */
    public function getKeteranganStatus()
    {
        return $this->keteranganStatus;
    }

    /**
     * Set idsiswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return KehadiranSiswa
     */
    public function setIdsiswa(\Fast\SisdikBundle\Entity\Siswa $idsiswa = null)
    {
        $this->idsiswa = $idsiswa;
    
        return $this;
    }

    /**
     * Get idsiswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getIdsiswa()
    {
        return $this->idsiswa;
    }

    /**
     * Set idkelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return KehadiranSiswa
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null)
    {
        $this->idkelas = $idkelas;
    
        return $this;
    }

    /**
     * Get idkelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas()
    {
        return $this->idkelas;
    }

    /**
     * Set idstatusKehadiranKepulangan
     *
     * @param \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan
     * @return KehadiranSiswa
     */
    public function setIdstatusKehadiranKepulangan(\Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan = null)
    {
        $this->idstatusKehadiranKepulangan = $idstatusKehadiranKepulangan;
    
        return $this;
    }

    /**
     * Get idstatusKehadiranKepulangan
     *
     * @return \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan 
     */
    public function getIdstatusKehadiranKepulangan()
    {
        return $this->idstatusKehadiranKepulangan;
    }
}