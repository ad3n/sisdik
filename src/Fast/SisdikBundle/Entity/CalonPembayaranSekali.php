<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\CalonPembayaranSekali
 *
 * @ORM\Table(name="calon_pembayaran_sekali")
 * @ORM\Entity
 */
class CalonPembayaranSekali
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
     * @var BiayaSekali
     *
     * @ORM\ManyToOne(targetEntity="BiayaSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idbiaya_sekali", referencedColumnName="id")
     * })
     */
    private $idbiayaSekali;

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
     * Set idbiayaSekali
     *
     * @param Fast\SisdikBundle\Entity\BiayaSekali $idbiayaSekali
     * @return CalonPembayaranSekali
     */
    public function setIdbiayaSekali(\Fast\SisdikBundle\Entity\BiayaSekali $idbiayaSekali = null)
    {
        $this->idbiayaSekali = $idbiayaSekali;
    
        return $this;
    }

    /**
     * Get idbiayaSekali
     *
     * @return Fast\SisdikBundle\Entity\BiayaSekali 
     */
    public function getIdbiayaSekali()
    {
        return $this->idbiayaSekali;
    }

    /**
     * Set idcalonSiswa
     *
     * @param Fast\SisdikBundle\Entity\CalonSiswa $idcalonSiswa
     * @return CalonPembayaranSekali
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