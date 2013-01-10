<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\PendidikanSiswa
 *
 * @ORM\Table(name="pendidikan_siswa")
 * @ORM\Entity
 */
class PendidikanSiswa
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
     * @var integer $jenjang
     *
     * @ORM\Column(name="jenjang", type="integer", nullable=true)
     */
    private $jenjang;

    /**
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string $alamat
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string $ijazah
     *
     * @ORM\Column(name="ijazah", type="string", length=400, nullable=true)
     */
    private $ijazah;

    /**
     * @var string $ijazahFile
     *
     * @ORM\Column(name="ijazah_file", type="string", length=300, nullable=true)
     */
    private $ijazahFile;

    /**
     * @var \DateTime $tahunmasuk
     *
     * @ORM\Column(name="tahunmasuk", type="date", nullable=true)
     */
    private $tahunmasuk;

    /**
     * @var \DateTime $tahunkeluar
     *
     * @ORM\Column(name="tahunkeluar", type="date", nullable=true)
     */
    private $tahunkeluar;

    /**
     * @var \DateTime $sttbTanggal
     *
     * @ORM\Column(name="sttb_tanggal", type="date", nullable=true)
     */
    private $sttbTanggal;

    /**
     * @var string $sttbNo
     *
     * @ORM\Column(name="sttb_no", type="string", length=100, nullable=true)
     */
    private $sttbNo;

    /**
     * @var string $sttbFile
     *
     * @ORM\Column(name="sttb_file", type="string", length=300, nullable=true)
     */
    private $sttbFile;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var Referensi
     *
     * @ORM\ManyToOne(targetEntity="Referensi")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idreferensi", referencedColumnName="id")
     * })
     */
    private $idreferensi;

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
     * Set jenjang
     *
     * @param integer $jenjang
     * @return PendidikanSiswa
     */
    public function setJenjang($jenjang)
    {
        $this->jenjang = $jenjang;
    
        return $this;
    }

    /**
     * Get jenjang
     *
     * @return integer 
     */
    public function getJenjang()
    {
        return $this->jenjang;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return PendidikanSiswa
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
     * Set alamat
     *
     * @param string $alamat
     * @return PendidikanSiswa
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
     * Set ijazah
     *
     * @param string $ijazah
     * @return PendidikanSiswa
     */
    public function setIjazah($ijazah)
    {
        $this->ijazah = $ijazah;
    
        return $this;
    }

    /**
     * Get ijazah
     *
     * @return string 
     */
    public function getIjazah()
    {
        return $this->ijazah;
    }

    /**
     * Set ijazahFile
     *
     * @param string $ijazahFile
     * @return PendidikanSiswa
     */
    public function setIjazahFile($ijazahFile)
    {
        $this->ijazahFile = $ijazahFile;
    
        return $this;
    }

    /**
     * Get ijazahFile
     *
     * @return string 
     */
    public function getIjazahFile()
    {
        return $this->ijazahFile;
    }

    /**
     * Set tahunmasuk
     *
     * @param \DateTime $tahunmasuk
     * @return PendidikanSiswa
     */
    public function setTahunmasuk($tahunmasuk)
    {
        $this->tahunmasuk = $tahunmasuk;
    
        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \DateTime 
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * Set tahunkeluar
     *
     * @param \DateTime $tahunkeluar
     * @return PendidikanSiswa
     */
    public function setTahunkeluar($tahunkeluar)
    {
        $this->tahunkeluar = $tahunkeluar;
    
        return $this;
    }

    /**
     * Get tahunkeluar
     *
     * @return \DateTime 
     */
    public function getTahunkeluar()
    {
        return $this->tahunkeluar;
    }

    /**
     * Set sttbTanggal
     *
     * @param \DateTime $sttbTanggal
     * @return PendidikanSiswa
     */
    public function setSttbTanggal($sttbTanggal)
    {
        $this->sttbTanggal = $sttbTanggal;
    
        return $this;
    }

    /**
     * Get sttbTanggal
     *
     * @return \DateTime 
     */
    public function getSttbTanggal()
    {
        return $this->sttbTanggal;
    }

    /**
     * Set sttbNo
     *
     * @param string $sttbNo
     * @return PendidikanSiswa
     */
    public function setSttbNo($sttbNo)
    {
        $this->sttbNo = $sttbNo;
    
        return $this;
    }

    /**
     * Get sttbNo
     *
     * @return string 
     */
    public function getSttbNo()
    {
        return $this->sttbNo;
    }

    /**
     * Set sttbFile
     *
     * @param string $sttbFile
     * @return PendidikanSiswa
     */
    public function setSttbFile($sttbFile)
    {
        $this->sttbFile = $sttbFile;
    
        return $this;
    }

    /**
     * Get sttbFile
     *
     * @return string 
     */
    public function getSttbFile()
    {
        return $this->sttbFile;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return PendidikanSiswa
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
     * Set idreferensi
     *
     * @param Fast\SisdikBundle\Entity\Referensi $idreferensi
     * @return PendidikanSiswa
     */
    public function setIdreferensi(\Fast\SisdikBundle\Entity\Referensi $idreferensi = null)
    {
        $this->idreferensi = $idreferensi;
    
        return $this;
    }

    /**
     * Get idreferensi
     *
     * @return Fast\SisdikBundle\Entity\Referensi 
     */
    public function getIdreferensi()
    {
        return $this->idreferensi;
    }

    /**
     * Set idsiswa
     *
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return PendidikanSiswa
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