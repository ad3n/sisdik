<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WaliKelas
 *
 * @ORM\Table(name="wali_kelas", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="wali_kelas_unq1", columns={"tahun_akademik_id", "kelas_id"})
 * })
 * @ORM\Entity
 */
class WaliKelas
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
     * @var string
     *
     * @ORM\Column(name="nama", type="string", length=45, nullable=true)
     */
    private $nama;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $kelas;

    /**
     * @var \TahunAkademik
     *
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunAkademik;



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
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return WaliKelas
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    
        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * Set tahunAkademik
     *
     * @param \Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @return WaliKelas
     */
    public function setTahunAkademik(\Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    
        return $this;
    }

    /**
     * Get tahunAkademik
     *
     * @return \Fast\SisdikBundle\Entity\TahunAkademik 
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }
}