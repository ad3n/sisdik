<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CalonPembayaranRutin
 *
 * @ORM\Table(name="calon_pembayaran_rutin")
 * @ORM\Entity
 */
class CalonPembayaranRutin
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
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=true)
     */
    private $nominalPembayaran;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     */
    private $keterangan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_catat", type="datetime", nullable=true)
     */
    private $waktuCatat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     */
    private $waktuUbah;

    /**
     * @var \BiayaRutin
     *
     * @ORM\ManyToOne(targetEntity="BiayaRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="biaya_rutin_id", referencedColumnName="id")
     * })
     */
    private $biayaRutin;

    /**
     * @var \CalonSiswa
     *
     * @ORM\ManyToOne(targetEntity="CalonSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_siswa_id", referencedColumnName="id")
     * })
     */
    private $calonSiswa;



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
     * Set nominalPembayaran
     *
     * @param integer $nominalPembayaran
     * @return CalonPembayaranRutin
     */
    public function setNominalPembayaran($nominalPembayaran)
    {
        $this->nominalPembayaran = $nominalPembayaran;
    
        return $this;
    }

    /**
     * Get nominalPembayaran
     *
     * @return integer 
     */
    public function getNominalPembayaran()
    {
        return $this->nominalPembayaran;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return CalonPembayaranRutin
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
     * Set waktuCatat
     *
     * @param \DateTime $waktuCatat
     * @return CalonPembayaranRutin
     */
    public function setWaktuCatat($waktuCatat)
    {
        $this->waktuCatat = $waktuCatat;
    
        return $this;
    }

    /**
     * Get waktuCatat
     *
     * @return \DateTime 
     */
    public function getWaktuCatat()
    {
        return $this->waktuCatat;
    }

    /**
     * Set waktuUbah
     *
     * @param \DateTime $waktuUbah
     * @return CalonPembayaranRutin
     */
    public function setWaktuUbah($waktuUbah)
    {
        $this->waktuUbah = $waktuUbah;
    
        return $this;
    }

    /**
     * Get waktuUbah
     *
     * @return \DateTime 
     */
    public function getWaktuUbah()
    {
        return $this->waktuUbah;
    }

    /**
     * Set biayaRutin
     *
     * @param \Fast\SisdikBundle\Entity\BiayaRutin $biayaRutin
     * @return CalonPembayaranRutin
     */
    public function setBiayaRutin(\Fast\SisdikBundle\Entity\BiayaRutin $biayaRutin = null)
    {
        $this->biayaRutin = $biayaRutin;
    
        return $this;
    }

    /**
     * Get biayaRutin
     *
     * @return \Fast\SisdikBundle\Entity\BiayaRutin 
     */
    public function getBiayaRutin()
    {
        return $this->biayaRutin;
    }

    /**
     * Set calonSiswa
     *
     * @param \Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa
     * @return CalonPembayaranRutin
     */
    public function setCalonSiswa(\Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa = null)
    {
        $this->calonSiswa = $calonSiswa;
    
        return $this;
    }

    /**
     * Get calonSiswa
     *
     * @return \Fast\SisdikBundle\Entity\CalonSiswa 
     */
    public function getCalonSiswa()
    {
        return $this->calonSiswa;
    }
}