<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\CukilMp
 *
 * @ORM\Table(name="cukil_mp")
 * @ORM\Entity
 */
class CukilMp
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
     * @var Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahun", referencedColumnName="id")
     * })
     */
    private $idtahun;

    /**
     * @var Semester
     *
     * @ORM\ManyToOne(targetEntity="Semester")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsemester", referencedColumnName="id")
     * })
     */
    private $idsemester;

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
     * Set idtahun
     *
     * @param Fast\SisdikBundle\Entity\Tahun $idtahun
     * @return CukilMp
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

    /**
     * Set idsemester
     *
     * @param Fast\SisdikBundle\Entity\Semester $idsemester
     * @return CukilMp
     */
    public function setIdsemester(\Fast\SisdikBundle\Entity\Semester $idsemester = null)
    {
        $this->idsemester = $idsemester;
    
        return $this;
    }

    /**
     * Get idsemester
     *
     * @return Fast\SisdikBundle\Entity\Semester 
     */
    public function getIdsemester()
    {
        return $this->idsemester;
    }

    /**
     * Set idmataPelajaran
     *
     * @param Fast\SisdikBundle\Entity\MataPelajaran $idmataPelajaran
     * @return CukilMp
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