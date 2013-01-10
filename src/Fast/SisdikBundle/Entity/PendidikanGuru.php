<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\PendidikanGuru
 *
 * @ORM\Table(name="pendidikan_guru")
 * @ORM\Entity
 */
class PendidikanGuru
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $jenjang
     *
     * @ORM\Column(name="jenjang", type="integer", nullable=true)
     */
    private $jenjang;

    /**
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string $alamat
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string $ijazah
     *
     * @ORM\Column(name="ijazah", type="string", length=400, nullable=true)
     */
    private $ijazah;

    /**
     * @var string $kelulusan
     *
     * @ORM\Column(name="kelulusan", type="string", length=500, nullable=true)
     */
    private $kelulusan;

    /**
     * @var \DateTime $tahunmasuk
     *
     * @ORM\Column(name="tahunmasuk", type="date", nullable=true)
     */
    private $tahunmasuk;

    /**
     * @var \DateTime $tahunkeluar
     *
     * @ORM\Column(name="tahunkeluar", type="date", nullable=true)
     */
    private $tahunkeluar;

    /**
     * @var Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idguru", referencedColumnName="id")
     * })
     */
    private $idguru;



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
     * Set jenjang
     *
     * @param integer $jenjang
     * @return PendidikanGuru
     */
    public function setJenjang($jenjang)
    {
        $this->jenjang = $jenjang;
    
        return $this;
    }

    /**
     * Get jenjang
     *
     * @return integer 
     */
    public function getJenjang()
    {
        return $this->jenjang;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return PendidikanGuru
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
     * Set alamat
     *
     * @param string $alamat
     * @return PendidikanGuru
     */
    public function setAlamat($alamat)
    {
        $this->alamat = $alamat;
    
        return $this;
    }

    /**
     * Get alamat
     *
     * @return string 
     */
    public function getAlamat()
    {
        return $this->alamat;
    }

    /**
     * Set ijazah
     *
     * @param string $ijazah
     * @return PendidikanGuru
     */
    public function setIjazah($ijazah)
    {
        $this->ijazah = $ijazah;
    
        return $this;
    }

    /**
     * Get ijazah
     *
     * @return string 
     */
    public function getIjazah()
    {
        return $this->ijazah;
    }

    /**
     * Set kelulusan
     *
     * @param string $kelulusan
     * @return PendidikanGuru
     */
    public function setKelulusan($kelulusan)
    {
        $this->kelulusan = $kelulusan;
    
        return $this;
    }

    /**
     * Get kelulusan
     *
     * @return string 
     */
    public function getKelulusan()
    {
        return $this->kelulusan;
    }

    /**
     * Set tahunmasuk
     *
     * @param \DateTime $tahunmasuk
     * @return PendidikanGuru
     */
    public function setTahunmasuk($tahunmasuk)
    {
        $this->tahunmasuk = $tahunmasuk;
    
        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \DateTime 
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * Set tahunkeluar
     *
     * @param \DateTime $tahunkeluar
     * @return PendidikanGuru
     */
    public function setTahunkeluar($tahunkeluar)
    {
        $this->tahunkeluar = $tahunkeluar;
    
        return $this;
    }

    /**
     * Get tahunkeluar
     *
     * @return \DateTime 
     */
    public function getTahunkeluar()
    {
        return $this->tahunkeluar;
    }

    /**
     * Set idguru
     *
     * @param Fast\SisdikBundle\Entity\Guru $idguru
     * @return PendidikanGuru
     */
    public function setIdguru(\Fast\SisdikBundle\Entity\Guru $idguru = null)
    {
        $this->idguru = $idguru;
    
        return $this;
    }

    /**
     * Get idguru
     *
     * @return Fast\SisdikBundle\Entity\Guru 
     */
    public function getIdguru()
    {
        return $this->idguru;
    }
}