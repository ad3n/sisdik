<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PembayaranRutin
 *
 * @ORM\Table(name="pembayaran_rutin")
 * @ORM\Entity
 */
class PembayaranRutin
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
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id")
     * })
     */
    private $siswa;

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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return PembayaranRutin
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    
        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getSiswa()
    {
        return $this->siswa;
    }

    /**
     * Set biayaRutin
     *
     * @param \Fast\SisdikBundle\Entity\BiayaRutin $biayaRutin
     * @return PembayaranRutin
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
}