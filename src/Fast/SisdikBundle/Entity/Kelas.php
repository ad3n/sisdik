<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Kelas
 *
 * @ORM\Table(name="kelas")
 * @ORM\Entity
 */
class Kelas
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
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     */
    private $kode;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=400, nullable=true)
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
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahun", referencedColumnName="id")
     * })
     */
    private $idtahun;

    /**
     * @var \Jenjang
     *
     * @ORM\ManyToOne(targetEntity="Jenjang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idjenjang", referencedColumnName="id")
     * })
     */
    private $idjenjang;



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
     * @return Kelas
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
     * @return Kelas
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
     * @return Kelas
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
     * @return Kelas
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
     * Set idsekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Kelas
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null)
    {
        $this->idsekolah = $idsekolah;
    
        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }

    /**
     * Set idtahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $idtahun
     * @return Kelas
     */
    public function setIdtahun(\Fast\SisdikBundle\Entity\Tahun $idtahun = null)
    {
        $this->idtahun = $idtahun;
    
        return $this;
    }

    /**
     * Get idtahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun 
     */
    public function getIdtahun()
    {
        return $this->idtahun;
    }

    /**
     * Set idjenjang
     *
     * @param \Fast\SisdikBundle\Entity\Jenjang $idjenjang
     * @return Kelas
     */
    public function setIdjenjang(\Fast\SisdikBundle\Entity\Jenjang $idjenjang = null)
    {
        $this->idjenjang = $idjenjang;
    
        return $this;
    }

    /**
     * Get idjenjang
     *
     * @return \Fast\SisdikBundle\Entity\Jenjang 
     */
    public function getIdjenjang()
    {
        return $this->idjenjang;
    }
}