<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * JenisDokumenSiswa
 *
 * @ORM\Table(name="jenis_dokumen_siswa")
 * @ORM\Entity
 */
class JenisDokumenSiswa
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
     * @ORM\Column(name="nama_dokumen", type="string", length=255, nullable=true)
     */
    private $namaDokumen;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $sekolah;

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
     * @return JenisDokumenSiswa
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
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return JenisDokumenSiswa
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $this->sekolah = $sekolah;

        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah
     */
    public function getSekolah() {
        return $this->sekolah;
    }
}
