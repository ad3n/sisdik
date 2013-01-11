<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CukilMp
 *
 * @ORM\Table(name="cukil_mp")
 * @ORM\Entity
 */
class CukilMp
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
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id")
     * })
     */
    private $tahun;

    /**
     * @var \Semester
     *
     * @ORM\ManyToOne(targetEntity="Semester")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="semester_id", referencedColumnName="id")
     * })
     */
    private $semester;

    /**
     * @var \MataPelajaran
     *
     * @ORM\ManyToOne(targetEntity="MataPelajaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mata_pelajaran_id", referencedColumnName="id")
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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return CukilMp
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
     * Set semester
     *
     * @param \Fast\SisdikBundle\Entity\Semester $semester
     * @return CukilMp
     */
    public function setSemester(\Fast\SisdikBundle\Entity\Semester $semester = null)
    {
        $this->semester = $semester;
    
        return $this;
    }

    /**
     * Get semester
     *
     * @return \Fast\SisdikBundle\Entity\Semester 
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * Set mataPelajaran
     *
     * @param \Fast\SisdikBundle\Entity\MataPelajaran $mataPelajaran
     * @return CukilMp
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