<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Guru
 *
 * @ORM\Table(name="guru")
 * @ORM\Entity
 */
class Guru
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
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="jenis_kelamin", type="string", length=255, nullable=true)
     */
    private $jenisKelamin;

    /**
     * @var string
     *
     * @ORM\Column(name="foto", type="string", length=400, nullable=true)
     */
    private $foto;

    /**
     * @var integer
     *
     * @ORM\Column(name="agama", type="integer", nullable=true)
     */
    private $agama;

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
     * @var string
     *
     * @ORM\Column(name="telepon", type="string", length=100, nullable=true)
     */
    private $telepon;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_induk", type="string", length=50, nullable=true)
     */
    private $nomorInduk;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
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
     * @return Guru
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
     * Set jenisKelamin
     *
     * @param string $jenisKelamin
     * @return Guru
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
     * Set foto
     *
     * @param string $foto
     * @return Guru
     */
    public function setFoto($foto)
    {
        $this->foto = $foto;
    
        return $this;
    }

    /**
     * Get foto
     *
     * @return string 
     */
    public function getFoto()
    {
        return $this->foto;
    }

    /**
     * Set agama
     *
     * @param integer $agama
     * @return Guru
     */
    public function setAgama($agama)
    {
        $this->agama = $agama;
    
        return $this;
    }

    /**
     * Get agama
     *
     * @return integer 
     */
    public function getAgama()
    {
        return $this->agama;
    }

    /**
     * Set tempatLahir
     *
     * @param string $tempatLahir
     * @return Guru
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
     * @return Guru
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
     * @return Guru
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
     * Set telepon
     *
     * @param string $telepon
     * @return Guru
     */
    public function setTelepon($telepon)
    {
        $this->telepon = $telepon;
    
        return $this;
    }

    /**
     * Get telepon
     *
     * @return string 
     */
    public function getTelepon()
    {
        return $this->telepon;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Guru
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set nomorInduk
     *
     * @param string $nomorInduk
     * @return Guru
     */
    public function setNomorInduk($nomorInduk)
    {
        $this->nomorInduk = $nomorInduk;
    
        return $this;
    }

    /**
     * Get nomorInduk
     *
     * @return string 
     */
    public function getNomorInduk()
    {
        return $this->nomorInduk;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Guru
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Guru
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return Guru
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