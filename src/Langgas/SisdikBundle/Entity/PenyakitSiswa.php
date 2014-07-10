<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="penyakit_siswa")
 * @ORM\Entity
 */
class PenyakitSiswa
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
     * @Assert\NotBlank
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kelas", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $kelas;

    /**
     * @ORM\Column(name="tahun", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $tahun;

    /**
     * @ORM\Column(name="lamasakit", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $lamasakit;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="penyakitSiswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

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
     * @param string $kelas
     */
    public function setKelas($kelas)
    {
        $this->kelas = $kelas;
    }

    /**
     * @return string
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * @param string $tahun
     */
    public function setTahun($tahun)
    {
        $this->tahun = $tahun;
    }

    /**
     * @return string
     */
    public function getTahun()
    {
        return $this->tahun;
    }

    /**
     * @param string $lamasakit
     */
    public function setLamasakit($lamasakit)
    {
        $this->lamasakit = $lamasakit;
    }

    /**
     * @return string
     */
    public function getLamasakit()
    {
        return $this->lamasakit;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }
}
