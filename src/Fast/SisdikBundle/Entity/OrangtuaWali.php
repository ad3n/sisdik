<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrangtuaWali
 *
 * @ORM\Table(name="orangtua_wali")
 * @ORM\Entity
 */
class OrangtuaWali
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
     * @ORM\Column(name="tempat_lahir", type="string", length=300, nullable=true)
     */
    private $tempatLahir;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal_lahir", type="date", nullable=true)
     */
    private $tanggalLahir;

    /**
     * @var string
     *
     * @ORM\Column(name="kewarganegaraan", type="string", length=200, nullable=true)
     */
    private $kewarganegaraan;

    /**
     * @var integer
     *
     * @ORM\Column(name="peran", type="integer", nullable=true)
     */
    private $peran;

    /**
     * @var string
     *
     * @ORM\Column(name="pendidikan_tertinggi", type="string", length=300, nullable=true)
     */
    private $pendidikanTertinggi;

    /**
     * @var string
     *
     * @ORM\Column(name="pekerjaan", type="string", length=300, nullable=true)
     */
    private $pekerjaan;

    /**
     * @var integer
     *
     * @ORM\Column(name="penghasilan_bulanan", type="integer", nullable=true)
     */
    private $penghasilanBulanan;

    /**
     * @var integer
     *
     * @ORM\Column(name="penghasilan_tahunan", type="integer", nullable=true)
     */
    private $penghasilanTahunan;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=400, nullable=true)
     */
    private $alamat;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;



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
     * @return OrangtuaWali
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
     * Set tempatLahir
     *
     * @param string $tempatLahir
     * @return OrangtuaWali
     */
    public function setTempatLahir($tempatLahir)
    {
        $this->tempatLahir = $tempatLahir;
    
        return $this;
    }

    /**
     * Get tempatLahir
     *
     * @return string 
     */
    public function getTempatLahir()
    {
        return $this->tempatLahir;
    }

    /**
     * Set tanggalLahir
     *
     * @param \DateTime $tanggalLahir
     * @return OrangtuaWali
     */
    public function setTanggalLahir($tanggalLahir)
    {
        $this->tanggalLahir = $tanggalLahir;
    
        return $this;
    }

    /**
     * Get tanggalLahir
     *
     * @return \DateTime 
     */
    public function getTanggalLahir()
    {
        return $this->tanggalLahir;
    }

    /**
     * Set kewarganegaraan
     *
     * @param string $kewarganegaraan
     * @return OrangtuaWali
     */
    public function setKewarganegaraan($kewarganegaraan)
    {
        $this->kewarganegaraan = $kewarganegaraan;
    
        return $this;
    }

    /**
     * Get kewarganegaraan
     *
     * @return string 
     */
    public function getKewarganegaraan()
    {
        return $this->kewarganegaraan;
    }

    /**
     * Set peran
     *
     * @param integer $peran
     * @return OrangtuaWali
     */
    public function setPeran($peran)
    {
        $this->peran = $peran;
    
        return $this;
    }

    /**
     * Get peran
     *
     * @return integer 
     */
    public function getPeran()
    {
        return $this->peran;
    }

    /**
     * Set pendidikanTertinggi
     *
     * @param string $pendidikanTertinggi
     * @return OrangtuaWali
     */
    public function setPendidikanTertinggi($pendidikanTertinggi)
    {
        $this->pendidikanTertinggi = $pendidikanTertinggi;
    
        return $this;
    }

    /**
     * Get pendidikanTertinggi
     *
     * @return string 
     */
    public function getPendidikanTertinggi()
    {
        return $this->pendidikanTertinggi;
    }

    /**
     * Set pekerjaan
     *
     * @param string $pekerjaan
     * @return OrangtuaWali
     */
    public function setPekerjaan($pekerjaan)
    {
        $this->pekerjaan = $pekerjaan;
    
        return $this;
    }

    /**
     * Get pekerjaan
     *
     * @return string 
     */
    public function getPekerjaan()
    {
        return $this->pekerjaan;
    }

    /**
     * Set penghasilanBulanan
     *
     * @param integer $penghasilanBulanan
     * @return OrangtuaWali
     */
    public function setPenghasilanBulanan($penghasilanBulanan)
    {
        $this->penghasilanBulanan = $penghasilanBulanan;
    
        return $this;
    }

    /**
     * Get penghasilanBulanan
     *
     * @return integer 
     */
    public function getPenghasilanBulanan()
    {
        return $this->penghasilanBulanan;
    }

    /**
     * Set penghasilanTahunan
     *
     * @param integer $penghasilanTahunan
     * @return OrangtuaWali
     */
    public function setPenghasilanTahunan($penghasilanTahunan)
    {
        $this->penghasilanTahunan = $penghasilanTahunan;
    
        return $this;
    }

    /**
     * Get penghasilanTahunan
     *
     * @return integer 
     */
    public function getPenghasilanTahunan()
    {
        return $this->penghasilanTahunan;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return OrangtuaWali
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
     * Set keterangan
     *
     * @param string $keterangan
     * @return OrangtuaWali
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
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return OrangtuaWali
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
}