<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="jadwal_bel")
 * @ORM\Entity
 */
class JadwalBel
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
     * @ORM\Column(name="hari", type="integer", nullable=true)
     *
     * @var integer
     */
    private $hari;

    /**
     * @ORM\Column(name="dari_jam", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $dariJam;

    /**
     * @ORM\Column(name="hingga_jam", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $hinggaJam;

    /**
     * @ORM\Column(name="berulang", type="boolean", nullable=false, options={"default" = 0})
     *
     * @var boolean
     */
    private $berulang;

    /**
     * @ORM\Column(name="file", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $file;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default" = 1})
     *
     * @var boolean
     */
    private $aktif;

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
     * @param integer $hari
     */
    public function setHari($hari)
    {
        $this->hari = $hari;
    }

    /**
     * @return integer
     */
    public function getHari()
    {
        return $this->hari;
    }

    /**
     * @param string $dariJam
     */
    public function setDariJam($dariJam)
    {
        $this->dariJam = $dariJam;
    }

    /**
     * @return string
     */
    public function getDariJam()
    {
        return $this->dariJam;
    }

    /**
     * @param string $hinggaJam
     */
    public function setHinggaJam($hinggaJam)
    {
        $this->hinggaJam = $hinggaJam;
    }

    /**
     * @return string
     */
    public function getHinggaJam()
    {
        return $this->hinggaJam;
    }

    /**
     * @param boolean $berulang
     */
    public function setBerulang($berulang)
    {
        $this->berulang = $berulang;
    }

    /**
     * @return boolean
     */
    public function getBerulang()
    {
        return $this->berulang;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
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
