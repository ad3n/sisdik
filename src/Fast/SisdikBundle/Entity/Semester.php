<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Semester
 *
 * @ORM\Table(name="semester")
 * @ORM\Entity
 */
class Semester
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
     * @ORM\Column(name="nama", type="string", length=100, nullable=true)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=true)
     */
    private $kode;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var integer
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id")
     * })
     */
    private $sekolah;



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
     * @return Semester
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
     * @return Semester
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
     * Set keterangan
     *
     * @param string $keterangan
     * @return Semester
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
     * Set urutan
     *
     * @param integer $urutan
     * @return Semester
     */
    public function setUrutan($urutan)
    {
        $this->urutan = $urutan;
    
        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer 
     */
    public function getUrutan()
    {
        return $this->urutan;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return Semester
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    
        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }
}