<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\PenyakitSiswa
 *
 * @ORM\Table(name="penyakit_siswa")
 * @ORM\Entity
 */
class PenyakitSiswa
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
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string $kelas
     *
     * @ORM\Column(name="kelas", type="string", length=200, nullable=true)
     */
    private $kelas;

    /**
     * @var string $tahun
     *
     * @ORM\Column(name="tahun", type="string", length=100, nullable=true)
     */
    private $tahun;

    /**
     * @var string $lamasakit
     *
     * @ORM\Column(name="lamasakit", type="string", length=200, nullable=true)
     */
    private $lamasakit;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

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
     * Set nama
     *
     * @param string $nama
     * @return PenyakitSiswa
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
     * Set kelas
     *
     * @param string $kelas
     * @return PenyakitSiswa
     */
    public function setKelas($kelas)
    {
        $this->kelas = $kelas;
    
        return $this;
    }

    /**
     * Get kelas
     *
     * @return string 
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * Set tahun
     *
     * @param string $tahun
     * @return PenyakitSiswa
     */
    public function setTahun($tahun)
    {
        $this->tahun = $tahun;
    
        return $this;
    }

    /**
     * Get tahun
     *
     * @return string 
     */
    public function getTahun()
    {
        return $this->tahun;
    }

    /**
     * Set lamasakit
     *
     * @param string $lamasakit
     * @return PenyakitSiswa
     */
    public function setLamasakit($lamasakit)
    {
        $this->lamasakit = $lamasakit;
    
        return $this;
    }

    /**
     * Get lamasakit
     *
     * @return string 
     */
    public function getLamasakit()
    {
        return $this->lamasakit;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return PenyakitSiswa
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
     * Set idsiswa
     *
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return PenyakitSiswa
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