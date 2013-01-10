<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\PembayaranSekali
 *
 * @ORM\Table(name="pembayaran_sekali")
 * @ORM\Entity
 */
class PembayaranSekali
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
     * @var Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsiswa", referencedColumnName="id")
     * })
     */
    private $idsiswa;



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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * Set idsiswa
     *
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return PembayaranSekali
     */
    public function setIdsiswa(\Fast\SisdikBundle\Entity\Siswa $idsiswa = null)
    {
        $this->idsiswa = $idsiswa;
    
        return $this;
    }

    /**
     * Get idsiswa
     *
     * @return Fast\SisdikBundle\Entity\Siswa 
     */
    public function getIdsiswa()
    {
        return $this->idsiswa;
    }
}