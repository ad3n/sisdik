<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Tahunmasuk
 *
 * @ORM\Table(name="tahunmasuk")
 * @ORM\Entity
 */
class Tahunmasuk
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
     * @var \DateTime $tahun
     *
     * @ORM\Column(name="tahun", type="string", nullable=false)
     */
    private $tahun;

    /**
     * @var Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;



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
     * Set tahun
     *
     * @param \DateTime $tahun
     * @return Tahunmasuk
     */
    public function setTahun($tahun)
    {
        $this->tahun = $tahun;
    
        return $this;
    }

    /**
     * Get tahun
     *
     * @return \DateTime 
     */
    public function getTahun()
    {
        return $this->tahun;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Tahunmasuk
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null)
    {
        $this->idsekolah = $idsekolah;
    
        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }
}