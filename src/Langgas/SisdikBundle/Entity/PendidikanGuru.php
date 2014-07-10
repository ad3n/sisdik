<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="pendidikan_guru")
 * @ORM\Entity
 */
class PendidikanGuru
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
     * @ORM\Column(name="jenjang", type="integer", nullable=true)
     *
     * @var integer
     */
    private $jenjang;

    /**
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="ijazah", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $ijazah;

    /**
     * @ORM\Column(name="kelulusan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $kelulusan;

    /**
     * @ORM\Column(name="tahunmasuk", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tahunmasuk;

    /**
     * @ORM\Column(name="tahunkeluar", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tahunkeluar;

    /**
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="guru_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Guru
     */
    private $guru;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $jenjang
     */
    public function setJenjang($jenjang)
    {
        $this->jenjang = $jenjang;
    }

    /**
     * @return integer
     */
    public function getJenjang()
    {
        return $this->jenjang;
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
     * @param string $ijazah
     */
    public function setIjazah($ijazah)
    {
        $this->ijazah = $ijazah;
    }

    /**
     * @return string
     */
    public function getIjazah()
    {
        return $this->ijazah;
    }

    /**
     * @param string $kelulusan
     */
    public function setKelulusan($kelulusan)
    {
        $this->kelulusan = $kelulusan;
    }

    /**
     * @return string
     */
    public function getKelulusan()
    {
        return $this->kelulusan;
    }

    /**
     * @param \DateTime $tahunmasuk
     */
    public function setTahunmasuk($tahunmasuk)
    {
        $this->tahunmasuk = $tahunmasuk;
    }

    /**
     * @return \DateTime
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * @param \DateTime $tahunkeluar
     */
    public function setTahunkeluar($tahunkeluar)
    {
        $this->tahunkeluar = $tahunkeluar;
    }

    /**
     * @return \DateTime
     */
    public function getTahunkeluar()
    {
        return $this->tahunkeluar;
    }

    /**
     * @param Guru $guru
     */
    public function setGuru(Guru $guru = null)
    {
        $this->guru = $guru;
    }

    /**
     * @return Guru
     */
    public function getGuru()
    {
        return $this->guru;
    }
}
