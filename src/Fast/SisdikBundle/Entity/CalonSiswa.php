<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\CalonSiswa
 *
 * @ORM\Table(name="calon_siswa")
 * @ORM\Entity
 */
class CalonSiswa
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
     * @var integer $nomorPendaftaran
     *
     * @ORM\Column(name="nomor_pendaftaran", type="integer", nullable=true)
     */
    private $nomorPendaftaran;

    /**
     * @var string $namaLengkap
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     */
    private $namaLengkap;

    /**
     * @var string $jenisKelamin
     *
     * @ORM\Column(name="jenis_kelamin", type="string", length=255, nullable=true)
     */
    private $jenisKelamin;

    /**
     * @var string $tempatLahir
     *
     * @ORM\Column(name="tempat_lahir", type="string", length=400, nullable=true)
     */
    private $tempatLahir;

    /**
     * @var \DateTime $tanggalLahir
     *
     * @ORM\Column(name="tanggal_lahir", type="date", nullable=true)
     */
    private $tanggalLahir;

    /**
     * @var string $alamat
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idgelombang", referencedColumnName="id")
     * })
     */
    private $idgelombang;

    /**
     * @var Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahunmasuk", referencedColumnName="id")
     * })
     */
    private $idtahunmasuk;

    /**
     * @var Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;



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
     * Set idgelombang
     *
     * @param Fast\SisdikBundle\Entity\Gelombang $idgelombang
     * @return CalonSiswa
     */
    public function setIdgelombang(\Fast\SisdikBundle\Entity\Gelombang $idgelombang = null)
    {
        $this->idgelombang = $idgelombang;
    
        return $this;
    }

    /**
     * Get idgelombang
     *
     * @return Fast\SisdikBundle\Entity\Gelombang 
     */
    public function getIdgelombang()
    {
        return $this->idgelombang;
    }

    /**
     * Set idtahunmasuk
     *
     * @param Fast\SisdikBundle\Entity\Tahunmasuk $idtahunmasuk
     * @return CalonSiswa
     */
    public function setIdtahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $idtahunmasuk = null)
    {
        $this->idtahunmasuk = $idtahunmasuk;
    
        return $this;
    }

    /**
     * Get idtahunmasuk
     *
     * @return Fast\SisdikBundle\Entity\Tahunmasuk 
     */
    public function getIdtahunmasuk()
    {
        return $this->idtahunmasuk;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return CalonSiswa
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null)
    {
        $this->idsekolah = $idsekolah;
    
        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }
}