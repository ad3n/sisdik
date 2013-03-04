<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GuruMp
 *
 * @ORM\Table(name="guru_mp")
 * @ORM\Entity
 */
class GuruMp
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
     * @var \Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guru_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $guru;

    /**
     * @var \MataPelajaran
     *
     * @ORM\ManyToOne(targetEntity="MataPelajaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mata_pelajaran_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $mataPelajaran;



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
     * Set guru
     *
     * @param \Fast\SisdikBundle\Entity\Guru $guru
     * @return GuruMp
     */
    public function setGuru(\Fast\SisdikBundle\Entity\Guru $guru = null)
    {
        $this->guru = $guru;
    
        return $this;
    }

    /**
     * Get guru
     *
     * @return \Fast\SisdikBundle\Entity\Guru 
     */
    public function getGuru()
    {
        return $this->guru;
    }

    /**
     * Set mataPelajaran
     *
     * @param \Fast\SisdikBundle\Entity\MataPelajaran $mataPelajaran
     * @return GuruMp
     */
    public function setMataPelajaran(\Fast\SisdikBundle\Entity\MataPelajaran $mataPelajaran = null)
    {
        $this->mataPelajaran = $mataPelajaran;
    
        return $this;
    }

    /**
     * Get mataPelajaran
     *
     * @return \Fast\SisdikBundle\Entity\MataPelajaran 
     */
    public function getMataPelajaran()
    {
        return $this->mataPelajaran;
    }
}