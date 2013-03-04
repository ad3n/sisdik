<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SiswaCmp
 *
 * @ORM\Table(name="siswa_cmp")
 * @ORM\Entity
 */
class SiswaCmp
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="nilai", type="integer", nullable=true)
     */
    private $nilai;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \JenisNilai
     *
     * @ORM\ManyToOne(targetEntity="JenisNilai")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenis_nilai_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $jenisNilai;

    /**
     * @var \CukilMp
     *
     * @ORM\ManyToOne(targetEntity="CukilMp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cukil_mp_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $cukilMp;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;



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
     * Set nilai
     *
     * @param integer $nilai
     * @return SiswaCmp
     */
    public function setNilai($nilai)
    {
        $this->nilai = $nilai;
    
        return $this;
    }

    /**
     * Get nilai
     *
     * @return integer 
     */
    public function getNilai()
    {
        return $this->nilai;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return SiswaCmp
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    
        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string 
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * Set jenisNilai
     *
     * @param \Fast\SisdikBundle\Entity\JenisNilai $jenisNilai
     * @return SiswaCmp
     */
    public function setJenisNilai(\Fast\SisdikBundle\Entity\JenisNilai $jenisNilai = null)
    {
        $this->jenisNilai = $jenisNilai;
    
        return $this;
    }

    /**
     * Get jenisNilai
     *
     * @return \Fast\SisdikBundle\Entity\JenisNilai 
     */
    public function getJenisNilai()
    {
        return $this->jenisNilai;
    }

    /**
     * Set cukilMp
     *
     * @param \Fast\SisdikBundle\Entity\CukilMp $cukilMp
     * @return SiswaCmp
     */
    public function setCukilMp(\Fast\SisdikBundle\Entity\CukilMp $cukilMp = null)
    {
        $this->cukilMp = $cukilMp;
    
        return $this;
    }

    /**
     * Get cukilMp
     *
     * @return \Fast\SisdikBundle\Entity\CukilMp 
     */
    public function getCukilMp()
    {
        return $this->cukilMp;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return SiswaCmp
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    
        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getSiswa()
    {
        return $this->siswa;
    }
}