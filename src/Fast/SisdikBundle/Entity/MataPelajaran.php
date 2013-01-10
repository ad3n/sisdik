<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\MataPelajaran
 *
 * @ORM\Table(name="mata_pelajaran")
 * @ORM\Entity
 */
class MataPelajaran
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
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string $kode
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=true)
     */
    private $kode;

    /**
     * @var string $penanggungJawab
     *
     * @ORM\Column(name="penanggung_jawab", type="string", length=400, nullable=true)
     */
    private $penanggungJawab;

    /**
     * @var integer $jumlahJam
     *
     * @ORM\Column(name="jumlah_jam", type="integer", nullable=true)
     */
    private $jumlahJam;

    /**
     * @var string $standarKompetensi
     *
     * @ORM\Column(name="standar_kompetensi", type="string", length=200, nullable=true)
     */
    private $standarKompetensi;

    /**
     * @var KelompokMp
     *
     * @ORM\ManyToOne(targetEntity="KelompokMp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelompok_mp", referencedColumnName="id")
     * })
     */
    private $idkelompokMp;



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
     * Set nama
     *
     * @param string $nama
     * @return MataPelajaran
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
     * Set kode
     *
     * @param string $kode
     * @return MataPelajaran
     */
    public function setKode($kode)
    {
        $this->kode = $kode;
    
        return $this;
    }

    /**
     * Get kode
     *
     * @return string 
     */
    public function getKode()
    {
        return $this->kode;
    }

    /**
     * Set penanggungJawab
     *
     * @param string $penanggungJawab
     * @return MataPelajaran
     */
    public function setPenanggungJawab($penanggungJawab)
    {
        $this->penanggungJawab = $penanggungJawab;
    
        return $this;
    }

    /**
     * Get penanggungJawab
     *
     * @return string 
     */
    public function getPenanggungJawab()
    {
        return $this->penanggungJawab;
    }

    /**
     * Set jumlahJam
     *
     * @param integer $jumlahJam
     * @return MataPelajaran
     */
    public function setJumlahJam($jumlahJam)
    {
        $this->jumlahJam = $jumlahJam;
    
        return $this;
    }

    /**
     * Get jumlahJam
     *
     * @return integer 
     */
    public function getJumlahJam()
    {
        return $this->jumlahJam;
    }

    /**
     * Set standarKompetensi
     *
     * @param string $standarKompetensi
     * @return MataPelajaran
     */
    public function setStandarKompetensi($standarKompetensi)
    {
        $this->standarKompetensi = $standarKompetensi;
    
        return $this;
    }

    /**
     * Get standarKompetensi
     *
     * @return string 
     */
    public function getStandarKompetensi()
    {
        return $this->standarKompetensi;
    }

    /**
     * Set idkelompokMp
     *
     * @param Fast\SisdikBundle\Entity\KelompokMp $idkelompokMp
     * @return MataPelajaran
     */
    public function setIdkelompokMp(\Fast\SisdikBundle\Entity\KelompokMp $idkelompokMp = null)
    {
        $this->idkelompokMp = $idkelompokMp;
    
        return $this;
    }

    /**
     * Get idkelompokMp
     *
     * @return Fast\SisdikBundle\Entity\KelompokMp 
     */
    public function getIdkelompokMp()
    {
        return $this->idkelompokMp;
    }
}