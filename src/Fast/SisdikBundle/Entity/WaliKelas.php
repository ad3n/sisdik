<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\WaliKelas
 *
 * @ORM\Table(name="wali_kelas")
 * @ORM\Entity
 */
class WaliKelas
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
     * @ORM\Column(name="nama", type="string", length=45, nullable=true)
     */
    private $nama;

    /**
     * @var Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;

    /**
     * @var Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahun", referencedColumnName="id")
     * })
     */
    private $idtahun;



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
     * @return WaliKelas
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
     * Set idkelas
     *
     * @param Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return WaliKelas
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null)
    {
        $this->idkelas = $idkelas;
    
        return $this;
    }

    /**
     * Get idkelas
     *
     * @return Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas()
    {
        return $this->idkelas;
    }

    /**
     * Set idtahun
     *
     * @param Fast\SisdikBundle\Entity\Tahun $idtahun
     * @return WaliKelas
     */
    public function setIdtahun(\Fast\SisdikBundle\Entity\Tahun $idtahun = null)
    {
        $this->idtahun = $idtahun;
    
        return $this;
    }

    /**
     * Get idtahun
     *
     * @return Fast\SisdikBundle\Entity\Tahun 
     */
    public function getIdtahun()
    {
        return $this->idtahun;
    }
}