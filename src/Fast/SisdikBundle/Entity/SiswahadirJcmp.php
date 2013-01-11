<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SiswahadirJcmp
 *
 * @ORM\Table(name="siswahadir_jcmp")
 * @ORM\Entity
 */
class SiswahadirJcmp
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
     * @var boolean
     *
     * @ORM\Column(name="hadir", type="boolean", nullable=true)
     */
    private $hadir;

    /**
     * @var string
     *
     * @ORM\Column(name="jam_masuk", type="string", length=20, nullable=true)
     */
    private $jamMasuk;

    /**
     * @var string
     *
     * @ORM\Column(name="jam_keluar", type="string", length=20, nullable=true)
     */
    private $jamKeluar;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \JadwalCmp
     *
     * @ORM\ManyToOne(targetEntity="JadwalCmp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jadwal_cmp_id", referencedColumnName="id")
     * })
     */
    private $jadwalCmp;



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
     * Set hadir
     *
     * @param boolean $hadir
     * @return SiswahadirJcmp
     */
    public function setHadir($hadir)
    {
        $this->hadir = $hadir;
    
        return $this;
    }

    /**
     * Get hadir
     *
     * @return boolean 
     */
    public function getHadir()
    {
        return $this->hadir;
    }

    /**
     * Set jamMasuk
     *
     * @param string $jamMasuk
     * @return SiswahadirJcmp
     */
    public function setJamMasuk($jamMasuk)
    {
        $this->jamMasuk = $jamMasuk;
    
        return $this;
    }

    /**
     * Get jamMasuk
     *
     * @return string 
     */
    public function getJamMasuk()
    {
        return $this->jamMasuk;
    }

    /**
     * Set jamKeluar
     *
     * @param string $jamKeluar
     * @return SiswahadirJcmp
     */
    public function setJamKeluar($jamKeluar)
    {
        $this->jamKeluar = $jamKeluar;
    
        return $this;
    }

    /**
     * Get jamKeluar
     *
     * @return string 
     */
    public function getJamKeluar()
    {
        return $this->jamKeluar;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return SiswahadirJcmp
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
     * Set jadwalCmp
     *
     * @param \Fast\SisdikBundle\Entity\JadwalCmp $jadwalCmp
     * @return SiswahadirJcmp
     */
    public function setJadwalCmp(\Fast\SisdikBundle\Entity\JadwalCmp $jadwalCmp = null)
    {
        $this->jadwalCmp = $jadwalCmp;
    
        return $this;
    }

    /**
     * Get jadwalCmp
     *
     * @return \Fast\SisdikBundle\Entity\JadwalCmp 
     */
    public function getJadwalCmp()
    {
        return $this->jadwalCmp;
    }
}