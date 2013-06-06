<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SekolahAsal
 *
 * @ORM\Table(name="sekolah_asal")
 * @ORM\Entity
 */
class SekolahAsal
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
     * @ORM\Column(name="nama", type="string", length=200, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(min=3)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="kode", type="string", length=45, nullable=true)
     */
    private $kode;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=300, nullable=true)
     */
    private $alamat;

    /**
     * @var string
     *
     * @ORM\Column(name="penghubung", type="string", length=200, nullable=true)
     */
    private $penghubung;

    /**
     * @var string
     *
     * @ORM\Column(name="ponsel_penghubung", type="string", length=100, nullable=true)
     */
    private $ponselPenghubung;

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
     * @var \Siswa
     *
     * @ORM\OneToMany(targetEntity="Siswa", mappedBy="sekolahAsal")
     */
    private $siswa;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return SekolahAsal
     */
    public function setNama($nama) {
        $this->nama = $nama;

        return $this;
    }

    /**
     * Get nama
     *
     * @return string
     */
    public function getNama() {
        return $this->nama;
    }

    /**
     * Set kode
     *
     * @param string $kode
     * @return SekolahAsal
     */
    public function setKode($kode) {
        $this->kode = $kode;

        return $this;
    }

    /**
     * Get kode
     *
     * @return string
     */
    public function getKode() {
        return $this->kode;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return SekolahAsal
     */
    public function setAlamat($alamat) {
        $this->alamat = $alamat;

        return $this;
    }

    /**
     * Get alamat
     *
     * @return string
     */
    public function getAlamat() {
        return $this->alamat;
    }

    /**
     * Set penghubung
     *
     * @param string $penghubung
     * @return SekolahAsal
     */
    public function setPenghubung($penghubung) {
        $this->penghubung = $penghubung;

        return $this;
    }

    /**
     * Get penghubung
     *
     * @return string
     */
    public function getPenghubung() {
        return $this->penghubung;
    }

    /**
     * Set ponselPenghubung
     *
     * @param string $ponselPenghubung
     * @return SekolahAsal
     */
    public function setPonselPenghubung($ponselPenghubung) {
        $this->ponselPenghubung = $ponselPenghubung;

        return $this;
    }

    /**
     * Get ponselPenghubung
     *
     * @return string
     */
    public function getPonselPenghubung() {
        return $this->ponselPenghubung;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return SekolahAsal
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $this->sekolah = $sekolah;

        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah
     */
    public function getSekolah() {
        return $this->sekolah;
    }
}
