<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="guru")
 * @ORM\Entity
 */
class Guru
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="jenis_kelamin", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $jenisKelamin;

    /**
     * @ORM\Column(name="foto", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $foto;

    /**
     * @ORM\Column(name="agama", type="integer", nullable=true)
     *
     * @var integer
     */
    private $agama;

    /**
     * @ORM\Column(name="tempat_lahir", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $tempatLahir;

    /**
     * @ORM\Column(name="tanggal_lahir", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggalLahir;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="telepon", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $telepon;

    /**
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="nomor_induk", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $nomorInduk;

    /**
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(name="status", type="boolean", nullable=true)
     *
     * @var boolean
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $nama
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    }

    /**
     * @return string
     */
    public function getNama()
    {
        return $this->nama;
    }

    /**
     * @param string $jenisKelamin
     */
    public function setJenisKelamin($jenisKelamin)
    {
        $this->jenisKelamin = $jenisKelamin;
    }

    /**
     * @return string
     */
    public function getJenisKelamin()
    {
        return $this->jenisKelamin;
    }

    /**
     * @param string $foto
     */
    public function setFoto($foto)
    {
        $this->foto = $foto;
    }

    /**
     * @return string
     */
    public function getFoto()
    {
        return $this->foto;
    }

    /**
     * @param integer $agama
     */
    public function setAgama($agama)
    {
        $this->agama = $agama;
    }

    /**
     * @return integer
     */
    public function getAgama()
    {
        return $this->agama;
    }

    /**
     * @param string $tempatLahir
     */
    public function setTempatLahir($tempatLahir)
    {
        $this->tempatLahir = $tempatLahir;
    }

    /**
     * @return string
     */
    public function getTempatLahir()
    {
        return $this->tempatLahir;
    }

    /**
     * @param \DateTime $tanggalLahir
     */
    public function setTanggalLahir($tanggalLahir)
    {
        $this->tanggalLahir = $tanggalLahir;
    }

    /**
     * @return \DateTime
     */
    public function getTanggalLahir()
    {
        return $this->tanggalLahir;
    }

    /**
     * @param string $alamat
     */
    public function setAlamat($alamat)
    {
        $this->alamat = $alamat;
    }

    /**
     * @return string
     */
    public function getAlamat()
    {
        return $this->alamat;
    }

    /**
     * @param string $telepon
     */
    public function setTelepon($telepon)
    {
        $this->telepon = $telepon;
    }

    /**
     * @return string
     */
    public function getTelepon()
    {
        return $this->telepon;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $nomorInduk
     */
    public function setNomorInduk($nomorInduk)
    {
        $this->nomorInduk = $nomorInduk;
    }

    /**
     * @return string
     */
    public function getNomorInduk()
    {
        return $this->nomorInduk;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }
}
