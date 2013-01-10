<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\GuruMp
 *
 * @ORM\Table(name="guru_mp")
 * @ORM\Entity
 */
class GuruMp
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
     * @var Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idguru", referencedColumnName="id")
     * })
     */
    private $idguru;

    /**
     * @var MataPelajaran
     *
     * @ORM\ManyToOne(targetEntity="MataPelajaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idmata_pelajaran", referencedColumnName="id")
     * })
     */
    private $idmataPelajaran;



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
     * Set idguru
     *
     * @param Fast\SisdikBundle\Entity\Guru $idguru
     * @return GuruMp
     */
    public function setIdguru(\Fast\SisdikBundle\Entity\Guru $idguru = null)
    {
        $this->idguru = $idguru;
    
        return $this;
    }

    /**
     * Get idguru
     *
     * @return Fast\SisdikBundle\Entity\Guru 
     */
    public function getIdguru()
    {
        return $this->idguru;
    }

    /**
     * Set idmataPelajaran
     *
     * @param Fast\SisdikBundle\Entity\MataPelajaran $idmataPelajaran
     * @return GuruMp
     */
    public function setIdmataPelajaran(\Fast\SisdikBundle\Entity\MataPelajaran $idmataPelajaran = null)
    {
        $this->idmataPelajaran = $idmataPelajaran;
    
        return $this;
    }

    /**
     * Get idmataPelajaran
     *
     * @return Fast\SisdikBundle\Entity\MataPelajaran 
     */
    public function getIdmataPelajaran()
    {
        return $this->idmataPelajaran;
    }
}