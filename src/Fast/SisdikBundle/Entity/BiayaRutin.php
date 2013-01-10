<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\BiayaRutin
 *
 * @ORM\Table(name="biaya_rutin")
 * @ORM\Entity
 */
class BiayaRutin
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
     * @var integer $nominal
     *
     * @ORM\Column(name="nominal", type="bigint", nullable=true)
     */
    private $nominal;

    /**
     * @var string $perulangan
     *
     * @ORM\Column(name="perulangan", type="string", nullable=true)
     */
    private $perulangan;

    /**
     * @var integer $urutan
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

    /**
     * @var Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idgelombang", referencedColumnName="id")
     * })
     */
    private $idgelombang;

    /**
     * @var Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahunmasuk", referencedColumnName="id")
     * })
     */
    private $idtahunmasuk;

    /**
     * @var Jenisbiaya
     *
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idjenisbiaya", referencedColumnName="id")
     * })
     */
    private $idjenisbiaya;



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
     * Set idgelombang
     *
     * @param Fast\SisdikBundle\Entity\Gelombang $idgelombang
     * @return BiayaRutin
     */
    public function setIdgelombang(\Fast\SisdikBundle\Entity\Gelombang $idgelombang = null)
    {
        $this->idgelombang = $idgelombang;
    
        return $this;
    }

    /**
     * Get idgelombang
     *
     * @return Fast\SisdikBundle\Entity\Gelombang 
     */
    public function getIdgelombang()
    {
        return $this->idgelombang;
    }

    /**
     * Set idtahunmasuk
     *
     * @param Fast\SisdikBundle\Entity\Tahunmasuk $idtahunmasuk
     * @return BiayaRutin
     */
    public function setIdtahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $idtahunmasuk = null)
    {
        $this->idtahunmasuk = $idtahunmasuk;
    
        return $this;
    }

    /**
     * Get idtahunmasuk
     *
     * @return Fast\SisdikBundle\Entity\Tahunmasuk 
     */
    public function getIdtahunmasuk()
    {
        return $this->idtahunmasuk;
    }

    /**
     * Set idjenisbiaya
     *
     * @param Fast\SisdikBundle\Entity\Jenisbiaya $idjenisbiaya
     * @return BiayaRutin
     */
    public function setIdjenisbiaya(\Fast\SisdikBundle\Entity\Jenisbiaya $idjenisbiaya = null)
    {
        $this->idjenisbiaya = $idjenisbiaya;
    
        return $this;
    }

    /**
     * Get idjenisbiaya
     *
     * @return Fast\SisdikBundle\Entity\Jenisbiaya 
     */
    public function getIdjenisbiaya()
    {
        return $this->idjenisbiaya;
    }
}