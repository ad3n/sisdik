<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalonDokumen
 *
 * @ORM\Table(name="calon_dokumen")
 * @ORM\Entity
 */
class CalonDokumen
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
     * @ORM\Column(name="nama_dokumen", type="string", length=100, nullable=true)
     */
    private $namaDokumen;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ada", type="boolean", nullable=true)
     */
    private $ada;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=100, nullable=true)
     */
    private $file;

    /**
     * @var \CalonSiswa
     *
     * @ORM\ManyToOne(targetEntity="CalonSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $calonSiswa;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set namaDokumen
     *
     * @param string $namaDokumen
     * @return CalonDokumen
     */
    public function setNamaDokumen($namaDokumen) {
        $this->namaDokumen = $namaDokumen;

        return $this;
    }

    /**
     * Get namaDokumen
     *
     * @return string
     */
    public function getNamaDokumen() {
        return $this->namaDokumen;
    }

    /**
     * Set ada
     *
     * @param boolean $ada
     * @return CalonDokumen
     */
    public function setAda($ada) {
        $this->ada = $ada;

        return $this;
    }

    /**
     * Get ada
     *
     * @return boolean
     */
    public function getAda() {
        return $this->ada;
    }

    /**
     * Set file
     *
     * @param string $file
     * @return CalonDokumen
     */
    public function setFile($file) {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Set calonSiswa
     *
     * @param \Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa
     * @return CalonDokumen
     */
    public function setCalonSiswa(\Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa = null) {
        $this->calonSiswa = $calonSiswa;

        return $this;
    }

    /**
     * Get calonSiswa
     *
     * @return \Fast\SisdikBundle\Entity\CalonSiswa
     */
    public function getCalonSiswa() {
        return $this->calonSiswa;
    }
}
