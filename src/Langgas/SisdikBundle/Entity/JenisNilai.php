<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="jenis_nilai")
 * @ORM\Entity
 */
class JenisNilai
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
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kode", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $kode;

    /**
     * @ORM\Column(name="bobot", type="integer", nullable=true)
     *
     * @var integer
     */
    private $bobot;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var TahunAkademik
     */
    private $tahunAkademik;

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
     * @param integer $bobot
     */
    public function setBobot($bobot)
    {
        $this->bobot = $bobot;
    }

    /**
     * @return integer
     */
    public function getBobot()
    {
        return $this->bobot;
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
     * @param TahunAkademik $tahunAkademik
     */
    public function setTahunAkademik(TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    }

    /**
     * @return TahunAkademik
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }
}
