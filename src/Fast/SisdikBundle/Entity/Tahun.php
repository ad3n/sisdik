<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tahun
 *
 * @ORM\Table(name="tahun")
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
     * @ORM\Column(name="aktif", type="boolean", nullable=false)
     */
    private $aktif = 0;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;

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
     * Set idsekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Tahun
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null) {
        $this->idsekolah = $idsekolah;

        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah() {
        return $this->idsekolah;
    }
}
