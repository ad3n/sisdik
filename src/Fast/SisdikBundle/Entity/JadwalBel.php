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
     * @ORM\Column(name="berulang", type="boolean", nullable=true)
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
     * @ORM\Column(name="aktif", type="boolean", nullable=true)
     */
    private $aktif;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id")
     * })
     */
    private $tahun;



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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return JadwalBel
     */
    public function setTahun(\Fast\SisdikBundle\Entity\Tahun $tahun = null)
    {
        $this->tahun = $tahun;
    
        return $this;
    }

    /**
     * Get tahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun 
     */
    public function getTahun()
    {
        return $this->tahun;
    }
}