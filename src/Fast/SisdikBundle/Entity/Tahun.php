<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tahun
 *
 * @ORM\Table(name="tahun", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="idsekolah_kode_UNIQ", columns={"sekolah_id", "kode"})
 * })
 * @ORM\Entity
 */
class Tahun
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
     * @ORM\Column(name="nama", type="string", length=50, nullable=true)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="kode", type="string", length=45, nullable=false)
     */
    private $kode;

    /**
     * @var integer
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var boolean
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default" = 0})
     */
    private $aktif = 0;

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
    public function getId() {
        return $this->id;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return Tahun
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
     * @return Tahun
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
     * Set urutan
     *
     * @param integer $urutan
     * @return Tahun
     */
    public function setUrutan($urutan) {
        $this->urutan = $urutan;

        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer 
     */
    public function getUrutan() {
        return $this->urutan;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return Tahun
     */
    public function setKeterangan($keterangan) {
        $this->keterangan = $keterangan;

        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string 
     */
    public function getKeterangan() {
        return $this->keterangan;
    }

    /**
     * Set aktif
     *
     * @param boolean $aktif
     * @return Tahun
     */
    public function setAktif($aktif) {
        $this->aktif = $aktif;

        return $this;
    }

    /**
     * Get aktif
     *
     * @return boolean 
     */
    public function getAktif() {
        return $this->aktif;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return Tahun
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
