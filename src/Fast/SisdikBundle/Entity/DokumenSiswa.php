<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DokumenSiswa
 *
 * @ORM\Table(name="dokumen_siswa")
 * @ORM\Entity
 */
class DokumenSiswa
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
     * @var boolean
     *
     * @ORM\Column(name="ada", type="boolean", nullable=false)
     */
    private $ada;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_file", type="string", length=255, nullable=true)
     */
    private $namaFile;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_file_disk", type="string", length=255, nullable=true)
     */
    private $namaFileDisk;

    /**
     * @var \JenisDokumenSiswa
     *
     * @ORM\ManyToOne(targetEntity="JenisDokumenSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenis_dokumen_siswa_id", referencedColumnName="id")
     * })
     */
    private $jenisDokumenSiswa;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id")
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
     * Set ada
     *
     * @param boolean $ada
     * @return DokumenSiswa
     */
    public function setAda($ada)
    {
        $this->ada = $ada;

        return $this;
    }

    /**
     * Get ada
     *
     * @return boolean 
     */
    public function getAda()
    {
        return $this->ada;
    }

    /**
     * Set namaFile
     *
     * @param string $namaFile
     * @return DokumenSiswa
     */
    public function setNamaFile($namaFile)
    {
        $this->namaFile = $namaFile;

        return $this;
    }

    /**
     * Get namaFile
     *
     * @return string 
     */
    public function getNamaFile()
    {
        return $this->namaFile;
    }

    /**
     * Set namaFileDisk
     *
     * @param string $namaFileDisk
     * @return DokumenSiswa
     */
    public function setNamaFileDisk($namaFileDisk)
    {
        $this->namaFileDisk = $namaFileDisk;

        return $this;
    }

    /**
     * Get namaFileDisk
     *
     * @return string 
     */
    public function getNamaFileDisk()
    {
        return $this->namaFileDisk;
    }

    /**
     * Set jenisDokumenSiswa
     *
     * @param \Fast\SisdikBundle\Entity\JenisDokumenSiswa $jenisDokumenSiswa
     * @return DokumenSiswa
     */
    public function setJenisDokumenSiswa(\Fast\SisdikBundle\Entity\JenisDokumenSiswa $jenisDokumenSiswa = null)
    {
        $this->jenisDokumenSiswa = $jenisDokumenSiswa;

        return $this;
    }

    /**
     * Get jenisDokumenSiswa
     *
     * @return \Fast\SisdikBundle\Entity\JenisDokumenSiswa 
     */
    public function getJenisDokumenSiswa()
    {
        return $this->jenisDokumenSiswa;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return DokumenSiswa
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
