<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BiayaRutin
 *
 * @ORM\Table(name="biaya_rutin", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UNQ_biaya_rutin1", columns={"jenisbiaya_id", "tahun_id", "gelombang_id"})
 * })
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
     * @ORM\Column(name="perulangan", type="string", nullable=true, options={"default"="bulan"})
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
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $gelombang;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahun;

    /**
     * @var \Jenisbiaya
     *
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return BiayaRutin
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