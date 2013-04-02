<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JadwalBel
 *
 * @ORM\Table(name="jadwal_bel")
 * @ORM\Entity
 */
class JadwalBel
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
     * @var integer
     *
     * @ORM\Column(name="hari", type="integer", nullable=true)
     */
    private $hari;

    /**
     * @var string
     *
     * @ORM\Column(name="dari_jam", type="string", length=50, nullable=true)
     */
    private $dariJam;

    /**
     * @var string
     *
     * @ORM\Column(name="hingga_jam", type="string", length=50, nullable=true)
     */
    private $hinggaJam;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berulang", type="boolean", nullable=false, options={"default" = 0})
     */
    private $berulang;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=100, nullable=true)
     */
    private $file;

    /**
     * @var boolean
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default" = 1})
     */
    private $aktif;

    /**
     * @var \TahunAkademik
     *
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunAkademik;



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
     * Set hari
     *
     * @param integer $hari
     * @return JadwalBel
     */
    public function setHari($hari)
    {
        $this->hari = $hari;
    
        return $this;
    }

    /**
     * Get hari
     *
     * @return integer 
     */
    public function getHari()
    {
        return $this->hari;
    }

    /**
     * Set dariJam
     *
     * @param string $dariJam
     * @return JadwalBel
     */
    public function setDariJam($dariJam)
    {
        $this->dariJam = $dariJam;
    
        return $this;
    }

    /**
     * Get dariJam
     *
     * @return string 
     */
    public function getDariJam()
    {
        return $this->dariJam;
    }

    /**
     * Set hinggaJam
     *
     * @param string $hinggaJam
     * @return JadwalBel
     */
    public function setHinggaJam($hinggaJam)
    {
        $this->hinggaJam = $hinggaJam;
    
        return $this;
    }

    /**
     * Get hinggaJam
     *
     * @return string 
     */
    public function getHinggaJam()
    {
        return $this->hinggaJam;
    }

    /**
     * Set berulang
     *
     * @param boolean $berulang
     * @return JadwalBel
     */
    public function setBerulang($berulang)
    {
        $this->berulang = $berulang;
    
        return $this;
    }

    /**
     * Get berulang
     *
     * @return boolean 
     */
    public function getBerulang()
    {
        return $this->berulang;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return JadwalBel
     */
    public function setFile($file)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set aktif
     *
     * @param boolean $aktif
     * @return JadwalBel
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    
        return $this;
    }

    /**
     * Get aktif
     *
     * @return boolean 
     */
    public function getAktif()
    {
        return $this->aktif;
    }

    /**
     * Set tahunAkademik
     *
     * @param \Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @return JadwalBel
     */
    public function setTahunAkademik(\Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    
        return $this;
    }

    /**
     * Get tahunAkademik
     *
     * @return \Fast\SisdikBundle\Entity\TahunAkademik 
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }
}