<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BiayaRutin
 *
 * @ORM\Table(name="biaya_rutin")
 * @ORM\Entity
 */
class BiayaRutin
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
     * @ORM\Column(name="nominal", type="bigint", nullable=true)
     */
    private $nominal;

    /**
     * @var string
     *
     * @ORM\Column(name="perulangan", type="string", nullable=true)
     */
    private $perulangan;

    /**
     * @var integer
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

    /**
     * @var \Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id")
     * })
     */
    private $gelombang;

    /**
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id")
     * })
     */
    private $tahunmasuk;

    /**
     * @var \Jenisbiaya
     *
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id")
     * })
     */
    private $jenisbiaya;



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
     * Set nominal
     *
     * @param integer $nominal
     * @return BiayaRutin
     */
    public function setNominal($nominal)
    {
        $this->nominal = $nominal;
    
        return $this;
    }

    /**
     * Get nominal
     *
     * @return integer 
     */
    public function getNominal()
    {
        return $this->nominal;
    }

    /**
     * Set perulangan
     *
     * @param string $perulangan
     * @return BiayaRutin
     */
    public function setPerulangan($perulangan)
    {
        $this->perulangan = $perulangan;
    
        return $this;
    }

    /**
     * Get perulangan
     *
     * @return string 
     */
    public function getPerulangan()
    {
        return $this->perulangan;
    }

    /**
     * Set urutan
     *
     * @param integer $urutan
     * @return BiayaRutin
     */
    public function setUrutan($urutan)
    {
        $this->urutan = $urutan;
    
        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer 
     */
    public function getUrutan()
    {
        return $this->urutan;
    }

    /**
     * Set gelombang
     *
     * @param \Fast\SisdikBundle\Entity\Gelombang $gelombang
     * @return BiayaRutin
     */
    public function setGelombang(\Fast\SisdikBundle\Entity\Gelombang $gelombang = null)
    {
        $this->gelombang = $gelombang;
    
        return $this;
    }

    /**
     * Get gelombang
     *
     * @return \Fast\SisdikBundle\Entity\Gelombang 
     */
    public function getGelombang()
    {
        return $this->gelombang;
    }

    /**
     * Set tahunmasuk
     *
     * @param \Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk
     * @return BiayaRutin
     */
    public function setTahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk = null)
    {
        $this->tahunmasuk = $tahunmasuk;
    
        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \Fast\SisdikBundle\Entity\Tahunmasuk 
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * Set jenisbiaya
     *
     * @param \Fast\SisdikBundle\Entity\Jenisbiaya $jenisbiaya
     * @return BiayaRutin
     */
    public function setJenisbiaya(\Fast\SisdikBundle\Entity\Jenisbiaya $jenisbiaya = null)
    {
        $this->jenisbiaya = $jenisbiaya;
    
        return $this;
    }

    /**
     * Get jenisbiaya
     *
     * @return \Fast\SisdikBundle\Entity\Jenisbiaya 
     */
    public function getJenisbiaya()
    {
        return $this->jenisbiaya;
    }
}