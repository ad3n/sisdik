<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tahun_akademik", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="idsekolah_kode_UNIQ", columns={"sekolah_id", "kode"})
 * })
 * @ORM\Entity
 */
class TahunAkademik
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
     * @ORM\Column(name="nama", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kode", type="string", length=45, nullable=false)
     *
     * @var string
     */
    private $kode;

    /**
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $urutan;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default" = 0})
     *
     * @var boolean
     */
    private $aktif = 0;

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
     * @param integer $urutan
     */
    public function setUrutan($urutan)
    {
        $this->urutan = $urutan;
    }

    /**
     * @return integer
     */
    public function getUrutan()
    {
        return $this->urutan;
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
     * @param boolean $aktif
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    }

    /**
     * @return boolean
     */
    public function getAktif()
    {
        return $this->aktif;
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
