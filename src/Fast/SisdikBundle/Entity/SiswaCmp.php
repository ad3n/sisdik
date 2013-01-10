<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\SiswaCmp
 *
 * @ORM\Table(name="siswa_cmp")
 * @ORM\Entity
 */
class SiswaCmp
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $nilai
     *
     * @ORM\Column(name="nilai", type="integer", nullable=true)
     */
    private $nilai;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var JenisNilai
     *
     * @ORM\ManyToOne(targetEntity="JenisNilai")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idjenis_nilai", referencedColumnName="id")
     * })
     */
    private $idjenisNilai;

    /**
     * @var CukilMp
     *
     * @ORM\ManyToOne(targetEntity="CukilMp")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idcukil_mp", referencedColumnName="id")
     * })
     */
    private $idcukilMp;

    /**
     * @var Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsiswa", referencedColumnName="id")
     * })
     */
    private $idsiswa;



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
     * Set idjenisNilai
     *
     * @param Fast\SisdikBundle\Entity\JenisNilai $idjenisNilai
     * @return SiswaCmp
     */
    public function setIdjenisNilai(\Fast\SisdikBundle\Entity\JenisNilai $idjenisNilai = null)
    {
        $this->idjenisNilai = $idjenisNilai;
    
        return $this;
    }

    /**
     * Get idjenisNilai
     *
     * @return Fast\SisdikBundle\Entity\JenisNilai 
     */
    public function getIdjenisNilai()
    {
        return $this->idjenisNilai;
    }

    /**
     * Set idcukilMp
     *
     * @param Fast\SisdikBundle\Entity\CukilMp $idcukilMp
     * @return SiswaCmp
     */
    public function setIdcukilMp(\Fast\SisdikBundle\Entity\CukilMp $idcukilMp = null)
    {
        $this->idcukilMp = $idcukilMp;
    
        return $this;
    }

    /**
     * Get idcukilMp
     *
     * @return Fast\SisdikBundle\Entity\CukilMp 
     */
    public function getIdcukilMp()
    {
        return $this->idcukilMp;
    }

    /**
     * Set idsiswa
     *
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return SiswaCmp
     */
    public function setIdsiswa(\Fast\SisdikBundle\Entity\Siswa $idsiswa = null)
    {
        $this->idsiswa = $idsiswa;
    
        return $this;
    }

    /**
     * Get idsiswa
     *
     * @return Fast\SisdikBundle\Entity\Siswa 
     */
    public function getIdsiswa()
    {
        return $this->idsiswa;
    }
}