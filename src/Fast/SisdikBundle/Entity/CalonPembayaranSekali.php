<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CalonPembayaranSekali
 *
 * @ORM\Table(name="calon_pembayaran_sekali")
 * @ORM\Entity
 */
class CalonPembayaranSekali
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
     * @var \BiayaSekali
     *
     * @ORM\ManyToOne(targetEntity="BiayaSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="biaya_sekali_id", referencedColumnName="id")
     * })
     */
    private $biayaSekali;

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
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * Set biayaSekali
     *
     * @param \Fast\SisdikBundle\Entity\BiayaSekali $biayaSekali
     * @return CalonPembayaranSekali
     */
    public function setBiayaSekali(\Fast\SisdikBundle\Entity\BiayaSekali $biayaSekali = null)
    {
        $this->biayaSekali = $biayaSekali;
    
        return $this;
    }

    /**
     * Get biayaSekali
     *
     * @return \Fast\SisdikBundle\Entity\BiayaSekali 
     */
    public function getBiayaSekali()
    {
        return $this->biayaSekali;
    }

    /**
     * Set calonSiswa
     *
     * @param \Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa
     * @return CalonPembayaranSekali
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