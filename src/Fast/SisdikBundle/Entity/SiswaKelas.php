<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SiswaKelas
 *
 * @ORM\Table(name="siswa_kelas")
 * @ORM\Entity
 */
class SiswaKelas
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
     * @var boolean
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false)
     */
    private $aktif;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=400, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id")
     * })
     */
    private $kelas;

    /**
     * @var \Penjurusan
     *
     * @ORM\ManyToOne(targetEntity="Penjurusan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="penjurusan_id", referencedColumnName="id")
     * })
     */
    private $penjurusan;

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
     * Set aktif
     *
     * @param boolean $aktif
     * @return SiswaKelas
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    
        return $this;
    }

    /**
     * Get aktif
     *
     * @return boolean 
     */
    public function getAktif()
    {
        return $this->aktif;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return SiswaKelas
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
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return SiswaKelas
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    
        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * Set penjurusan
     *
     * @param \Fast\SisdikBundle\Entity\Penjurusan $penjurusan
     * @return SiswaKelas
     */
    public function setPenjurusan(\Fast\SisdikBundle\Entity\Penjurusan $penjurusan = null)
    {
        $this->penjurusan = $penjurusan;
    
        return $this;
    }

    /**
     * Get penjurusan
     *
     * @return \Fast\SisdikBundle\Entity\Penjurusan 
     */
    public function getPenjurusan()
    {
        return $this->penjurusan;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return SiswaKelas
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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return SiswaKelas
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