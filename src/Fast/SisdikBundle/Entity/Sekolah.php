<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Sekolah
 *
 * @ORM\Table(name="sekolah")
 * @ORM\Entity
 */
class Sekolah
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
     * @ORM\Column(name="nama", type="string", length=300, nullable=false)
     */
    private $nama;

    /**
     * @var string $kode
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     */
    private $kode;

    /**
     * @var string $alamat
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string $kodepos
     *
     * @ORM\Column(name="kodepos", type="string", length=10, nullable=true)
     */
    private $kodepos;

    /**
     * @var string $telepon
     *
     * @ORM\Column(name="telepon", type="string", length=50, nullable=true)
     */
    private $telepon;

    /**
     * @var string $fax
     *
     * @ORM\Column(name="fax", type="string", length=50, nullable=true)
     */
    private $fax;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string $norekening
     *
     * @ORM\Column(name="norekening", type="string", length=100, nullable=true)
     */
    private $norekening;

    /**
     * @var string $bank
     *
     * @ORM\Column(name="bank", type="string", length=100, nullable=true)
     */
    private $bank;

    /**
     * @var string $kepsek
     *
     * @ORM\Column(name="kepsek", type="string", length=400, nullable=false)
     */
    private $kepsek;



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
     * @return Sekolah
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
     * @return Sekolah
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
     * Set alamat
     *
     * @param string $alamat
     * @return Sekolah
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
     * Set kodepos
     *
     * @param string $kodepos
     * @return Sekolah
     */
    public function setKodepos($kodepos)
    {
        $this->kodepos = $kodepos;
    
        return $this;
    }

    /**
     * Get kodepos
     *
     * @return string 
     */
    public function getKodepos()
    {
        return $this->kodepos;
    }

    /**
     * Set telepon
     *
     * @param string $telepon
     * @return Sekolah
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
     * Set fax
     *
     * @param string $fax
     * @return Sekolah
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    
        return $this;
    }

    /**
     * Get fax
     *
     * @return string 
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Sekolah
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
     * Set norekening
     *
     * @param string $norekening
     * @return Sekolah
     */
    public function setNorekening($norekening)
    {
        $this->norekening = $norekening;
    
        return $this;
    }

    /**
     * Get norekening
     *
     * @return string 
     */
    public function getNorekening()
    {
        return $this->norekening;
    }

    /**
     * Set bank
     *
     * @param string $bank
     * @return Sekolah
     */
    public function setBank($bank)
    {
        $this->bank = $bank;
    
        return $this;
    }

    /**
     * Get bank
     *
     * @return string 
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Set kepsek
     *
     * @param string $kepsek
     * @return Sekolah
     */
    public function setKepsek($kepsek)
    {
        $this->kepsek = $kepsek;
    
        return $this;
    }

    /**
     * Get kepsek
     *
     * @return string 
     */
    public function getKepsek()
    {
        return $this->kepsek;
    }
}