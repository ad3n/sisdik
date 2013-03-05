<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalonSiswa
 *
 * @ORM\Table(name="calon_siswa")
 * @ORM\Entity
 */
class CalonSiswa
{
    const WEBCAMPHOTO_DIR = 'uploads/applicants/webcam-photos/';
    
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
     * @ORM\Column(name="nomor_pendaftaran", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $nomorPendaftaran;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     * @Assert\NotBlank
     */
    private $namaLengkap;

    /**
     * @var string
     *
     * @ORM\Column(name="jenis_kelamin", type="string", length=255, nullable=true)
     * @Assert\NotBlank
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
     * @Assert\NotBlank
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
     * @ORM\Column(name="ponsel_orangtuawali", type="string", length=100, nullable=true)
     * @Assert\NotBlank()
     */
    private $ponselOrangtuawali;

    /**
     * @var string
     *
     * @ORM\Column(name="foto", type="string", length=100, nullable=true)
     */
    private $foto;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=false)
     */
    private $waktuUbah;

    /**
     * @var \Referensi
     *
     * @ORM\ManyToOne(targetEntity="Referensi")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referensi_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    private $referensi;

    /**
     * @var \Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $gelombang;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="diubah_oleh_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $diubahOleh;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dibuat_oleh_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $dibuatOleh;

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
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunmasuk;



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
     * Set ponselOrangtuawali
     *
     * @param string $ponselOrangtuawali
     * @return CalonSiswa
     */
    public function setPonselOrangtuawali($ponselOrangtuawali)
    {
        $this->ponselOrangtuawali = $ponselOrangtuawali;
    
        return $this;
    }

    /**
     * Get ponselOrangtuawali
     *
     * @return string 
     */
    public function getPonselOrangtuawali()
    {
        return $this->ponselOrangtuawali;
    }

    /**
     * Set foto
     *
     * @param string $foto
     * @return CalonSiswa
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
     * Set waktuSimpan
     *
     * @param \DateTime $waktuSimpan
     * @return CalonSiswa
     */
    public function setWaktuSimpan($waktuSimpan)
    {
        $this->waktuSimpan = $waktuSimpan;
    
        return $this;
    }

    /**
     * Get waktuSimpan
     *
     * @return \DateTime 
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * Set waktuUbah
     *
     * @param \DateTime $waktuUbah
     * @return CalonSiswa
     */
    public function setWaktuUbah($waktuUbah)
    {
        $this->waktuUbah = $waktuUbah;
    
        return $this;
    }

    /**
     * Get waktuUbah
     *
     * @return \DateTime 
     */
    public function getWaktuUbah()
    {
        return $this->waktuUbah;
    }

    /**
     * Set referensi
     *
     * @param \Fast\SisdikBundle\Entity\Referensi $referensi
     * @return CalonSiswa
     */
    public function setReferensi(\Fast\SisdikBundle\Entity\Referensi $referensi = null)
    {
        $this->referensi = $referensi;
    
        return $this;
    }

    /**
     * Get referensi
     *
     * @return \Fast\SisdikBundle\Entity\Referensi 
     */
    public function getReferensi()
    {
        return $this->referensi;
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
     * Set diubahOleh
     *
     * @param \Fast\SisdikBundle\Entity\User $diubahOleh
     * @return CalonSiswa
     */
    public function setDiubahOleh(\Fast\SisdikBundle\Entity\User $diubahOleh = null)
    {
        $this->diubahOleh = $diubahOleh;
    
        return $this;
    }

    /**
     * Get diubahOleh
     *
     * @return \Fast\SisdikBundle\Entity\User 
     */
    public function getDiubahOleh()
    {
        return $this->diubahOleh;
    }

    /**
     * Set dibuatOleh
     *
     * @param \Fast\SisdikBundle\Entity\User $dibuatOleh
     * @return CalonSiswa
     */
    public function setDibuatOleh(\Fast\SisdikBundle\Entity\User $dibuatOleh = null)
    {
        $this->dibuatOleh = $dibuatOleh;
    
        return $this;
    }

    /**
     * Get dibuatOleh
     *
     * @return \Fast\SisdikBundle\Entity\User 
     */
    public function getDibuatOleh()
    {
        return $this->dibuatOleh;
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
}