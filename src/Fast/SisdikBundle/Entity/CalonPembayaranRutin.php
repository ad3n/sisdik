<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\CalonPembayaranRutin
 *
 * @ORM\Table(name="calon_pembayaran_rutin")
 * @ORM\Entity
 */
class CalonPembayaranRutin
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
     * @var integer $nominalPembayaran
     *
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=true)
     */
    private $nominalPembayaran;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     */
    private $keterangan;

    /**
     * @var \DateTime $waktuCatat
     *
     * @ORM\Column(name="waktu_catat", type="datetime", nullable=true)
     */
    private $waktuCatat;

    /**
     * @var \DateTime $waktuUbah
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     */
    private $waktuUbah;

    /**
     * @var BiayaRutin
     *
     * @ORM\ManyToOne(targetEntity="BiayaRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idbiaya_rutin", referencedColumnName="id")
     * })
     */
    private $idbiayaRutin;

    /**
     * @var CalonSiswa
     *
     * @ORM\ManyToOne(targetEntity="CalonSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idcalon_siswa", referencedColumnName="id")
     * })
     */
    private $idcalonSiswa;



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
     * Set idbiayaRutin
     *
     * @param Fast\SisdikBundle\Entity\BiayaRutin $idbiayaRutin
     * @return CalonPembayaranRutin
     */
    public function setIdbiayaRutin(\Fast\SisdikBundle\Entity\BiayaRutin $idbiayaRutin = null)
    {
        $this->idbiayaRutin = $idbiayaRutin;
    
        return $this;
    }

    /**
     * Get idbiayaRutin
     *
     * @return Fast\SisdikBundle\Entity\BiayaRutin 
     */
    public function getIdbiayaRutin()
    {
        return $this->idbiayaRutin;
    }

    /**
     * Set idcalonSiswa
     *
     * @param Fast\SisdikBundle\Entity\CalonSiswa $idcalonSiswa
     * @return CalonPembayaranRutin
     */
    public function setIdcalonSiswa(\Fast\SisdikBundle\Entity\CalonSiswa $idcalonSiswa = null)
    {
        $this->idcalonSiswa = $idcalonSiswa;
    
        return $this;
    }

    /**
     * Get idcalonSiswa
     *
     * @return Fast\SisdikBundle\Entity\CalonSiswa 
     */
    public function getIdcalonSiswa()
    {
        return $this->idcalonSiswa;
    }
}