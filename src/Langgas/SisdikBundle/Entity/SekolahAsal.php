<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="sekolah_asal")
 * @ORM\Entity
 */
class SekolahAsal
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
     * @ORM\Column(name="nama", type="string", length=200, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(min=3)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kode", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $kode;

    /**
     * @ORM\Column(name="alamat", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="penghubung", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $penghubung;

    /**
     * @ORM\Column(name="ponsel_penghubung", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $ponselPenghubung;

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
     * @ORM\OneToMany(targetEntity="Siswa", mappedBy="sekolahAsal")
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
     * @param string $kode
     */
    public function setKode($kode)
    {
        $this->kode = $kode;
    }

    /**
     * @return string
     */
    public function getKode()
    {
        return $this->kode;
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
     * @param string $penghubung
     */
    public function setPenghubung($penghubung)
    {
        $this->penghubung = $penghubung;
    }

    /**
     * @return string
     */
    public function getPenghubung()
    {
        return $this->penghubung;
    }

    /**
     * @param string $ponselPenghubung
     */
    public function setPonselPenghubung($ponselPenghubung)
    {
        $this->ponselPenghubung = $ponselPenghubung;
    }

    /**
     * @return string
     */
    public function getPonselPenghubung()
    {
        return $this->ponselPenghubung;
    }

    /**
     * @param  $sekolah
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
