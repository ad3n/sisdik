<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CalonSiswa
 *
 * @ORM\Table(name="calon_siswa")
 * @ORM\Entity
 */
class CalonSiswa
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
     * @var integer
     *
     * @ORM\Column(name="nomor_pendaftaran", type="smallint", nullable=true)
     */
    private $nomorPendaftaran;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     */
    private $namaLengkap;

    /**
     * @var string
     *
     * @ORM\Column(name="jenis_kelamin", type="string", length=255, nullable=true)
     */
    private $jenisKelamin;

    /**
     * @var string
     *
     * @ORM\Column(name="tempat_lahir", type="string", length=400, nullable=true)
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
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var \Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id")
     * })
     */
    private $gelombang;

    /**
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id")
     * })
     */
    private $tahunmasuk;

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
     * Set nomorPendaftaran
     *
     * @param integer $nomorPendaftaran
     * @return CalonSiswa
     */
    public function setNomorPendaftaran($nomorPendaftaran)
    {
        $this->nomorPendaftaran = $nomorPendaftaran;
    
        return $this;
    }

    /**
     * Get nomorPendaftaran
     *
     * @return integer 
     */
    public function getNomorPendaftaran()
    {
        return $this->nomorPendaftaran;
    }

    /**
     * Set namaLengkap
     *
     * @param string $namaLengkap
     * @return CalonSiswa
     */
    public function setNamaLengkap($namaLengkap)
    {
        $this->namaLengkap = $namaLengkap;
    
        return $this;
    }

    /**
     * Get namaLengkap
     *
     * @return string 
     */
    public function getNamaLengkap()
    {
        return $this->namaLengkap;
    }

    /**
     * Set jenisKelamin
     *
     * @param string $jenisKelamin
     * @return CalonSiswa
     */
    public function setJenisKelamin($jenisKelamin)
    {
        $this->jenisKelamin = $jenisKelamin;
    
        return $this;
    }

    /**
     * Get jenisKelamin
     *
     * @return string 
     */
    public function getJenisKelamin()
    {
        return $this->jenisKelamin;
    }

    /**
     * Set tempatLahir
     *
     * @param string $tempatLahir
     * @return CalonSiswa
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
     * @return CalonSiswa
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
     * Set alamat
     *
     * @param string $alamat
     * @return CalonSiswa
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
     * Set gelombang
     *
     * @param \Fast\SisdikBundle\Entity\Gelombang $gelombang
     * @return CalonSiswa
     */
    public function setGelombang(\Fast\SisdikBundle\Entity\Gelombang $gelombang = null)
    {
        $this->gelombang = $gelombang;
    
        return $this;
    }

    /**
     * Get gelombang
     *
     * @return \Fast\SisdikBundle\Entity\Gelombang 
     */
    public function getGelombang()
    {
        return $this->gelombang;
    }

    /**
     * Set tahunmasuk
     *
     * @param \Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk
     * @return CalonSiswa
     */
    public function setTahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk = null)
    {
        $this->tahunmasuk = $tahunmasuk;
    
        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \Fast\SisdikBundle\Entity\Tahunmasuk 
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return CalonSiswa
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