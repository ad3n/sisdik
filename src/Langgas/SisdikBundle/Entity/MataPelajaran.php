<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mata_pelajaran")
 * @ORM\Entity
 */
class MataPelajaran
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
     * @ORM\Column(name="penanggung_jawab", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $penanggungJawab;

    /**
     * @ORM\Column(name="jumlah_jam", type="integer", nullable=true)
     *
     * @var integer
     */
    private $jumlahJam;

    /**
     * @ORM\Column(name="standar_kompetensi", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $standarKompetensi;

    /**
     * @ORM\ManyToOne(targetEntity="KelompokMp")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="kelompok_mp_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var KelompokMp
     */
    private $kelompokMp;

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
     * @param string $penanggungJawab
     */
    public function setPenanggungJawab($penanggungJawab)
    {
        $this->penanggungJawab = $penanggungJawab;
    }

    /**
     * @return string
     */
    public function getPenanggungJawab()
    {
        return $this->penanggungJawab;
    }

    /**
     * @param integer $jumlahJam
     */
    public function setJumlahJam($jumlahJam)
    {
        $this->jumlahJam = $jumlahJam;
    }

    /**
     * @return integer
     */
    public function getJumlahJam()
    {
        return $this->jumlahJam;
    }

    /**
     * @param string $standarKompetensi
     */
    public function setStandarKompetensi($standarKompetensi)
    {
        $this->standarKompetensi = $standarKompetensi;
    }

    /**
     * @return string
     */
    public function getStandarKompetensi()
    {
        return $this->standarKompetensi;
    }

    /**
     * @param KelompokMp $kelompokMp
     */
    public function setKelompokMp(KelompokMp $kelompokMp = null)
    {
        $this->kelompokMp = $kelompokMp;
    }

    /**
     * @return KelompokMp
     */
    public function getKelompokMp()
    {
        return $this->kelompokMp;
    }
}
