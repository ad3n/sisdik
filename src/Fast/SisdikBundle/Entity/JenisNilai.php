<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JenisNilai
 *
 * @ORM\Table(name="jenis_nilai")
 * @ORM\Entity
 */
class JenisNilai
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=true)
     */
    private $kode;

    /**
     * @var integer
     *
     * @ORM\Column(name="bobot", type="integer", nullable=true)
     */
    private $bobot;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id")
     * })
     */
    private $tahun;



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
     * Set nama
     *
     * @param string $nama
     * @return JenisNilai
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    
        return $this;
    }

    /**
     * Get nama
     *
     * @return string 
     */
    public function getNama()
    {
        return $this->nama;
    }

    /**
     * Set kode
     *
     * @param string $kode
     * @return JenisNilai
     */
    public function setKode($kode)
    {
        $this->kode = $kode;
    
        return $this;
    }

    /**
     * Get kode
     *
     * @return string 
     */
    public function getKode()
    {
        return $this->kode;
    }

    /**
     * Set bobot
     *
     * @param integer $bobot
     * @return JenisNilai
     */
    public function setBobot($bobot)
    {
        $this->bobot = $bobot;
    
        return $this;
    }

    /**
     * Get bobot
     *
     * @return integer 
     */
    public function getBobot()
    {
        return $this->bobot;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return JenisNilai
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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return JenisNilai
     */
    public function setTahun(\Fast\SisdikBundle\Entity\Tahun $tahun = null)
    {
        $this->tahun = $tahun;
    
        return $this;
    }

    /**
     * Get tahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun 
     */
    public function getTahun()
    {
        return $this->tahun;
    }
}