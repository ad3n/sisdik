<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\SiswaKelas
 *
 * @ORM\Table(name="siswa_kelas")
 * @ORM\Entity
 */
class SiswaKelas
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
     * @var boolean $aktif
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false)
     */
    private $aktif;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=400, nullable=true)
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
     * @var Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahun", referencedColumnName="id")
     * })
     */
    private $idtahun;

    /**
     * @var Penjurusan
     *
     * @ORM\ManyToOne(targetEntity="Penjurusan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idpenjurusan", referencedColumnName="id")
     * })
     */
    private $idpenjurusan;

    /**
     * @var Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;



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
     * Set idsiswa
     *
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return SiswaKelas
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

    /**
     * Set idtahun
     *
     * @param Fast\SisdikBundle\Entity\Tahun $idtahun
     * @return SiswaKelas
     */
    public function setIdtahun(\Fast\SisdikBundle\Entity\Tahun $idtahun = null)
    {
        $this->idtahun = $idtahun;
    
        return $this;
    }

    /**
     * Get idtahun
     *
     * @return Fast\SisdikBundle\Entity\Tahun 
     */
    public function getIdtahun()
    {
        return $this->idtahun;
    }

    /**
     * Set idpenjurusan
     *
     * @param Fast\SisdikBundle\Entity\Penjurusan $idpenjurusan
     * @return SiswaKelas
     */
    public function setIdpenjurusan(\Fast\SisdikBundle\Entity\Penjurusan $idpenjurusan = null)
    {
        $this->idpenjurusan = $idpenjurusan;
    
        return $this;
    }

    /**
     * Get idpenjurusan
     *
     * @return Fast\SisdikBundle\Entity\Penjurusan 
     */
    public function getIdpenjurusan()
    {
        return $this->idpenjurusan;
    }

    /**
     * Set idkelas
     *
     * @param Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return SiswaKelas
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null)
    {
        $this->idkelas = $idkelas;
    
        return $this;
    }

    /**
     * Get idkelas
     *
     * @return Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas()
    {
        return $this->idkelas;
    }
}