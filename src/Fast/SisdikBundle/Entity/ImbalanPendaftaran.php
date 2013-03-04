<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ImbalanPendaftaran
 *
 * @ORM\Table(name="imbalan_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_imbalan_pendaftaran1",
 *     columns={"tahunmasuk_id", "gelombang_id", "jenis_imbalan_id"})
 * })
 * @ORM\Entity
 */
class ImbalanPendaftaran
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
     * @var integer
     *
     * @ORM\Column(name="nominal", type="integer", nullable=false, options={"default" = 0})
     */
    private $nominal;

    /**
     * @var \JenisImbalan
     *
     * @ORM\ManyToOne(targetEntity="JenisImbalan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenis_imbalan_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $jenisImbalan;

    /**
     * @var \Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $gelombang;

    /**
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunmasuk;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nominal
     *
     * @param integer $nominal
     * @return ImbalanPendaftaran
     */
    public function setNominal($nominal) {
        $this->nominal = $nominal;

        return $this;
    }

    /**
     * Get nominal
     *
     * @return integer 
     */
    public function getNominal() {
        return $this->nominal;
    }

    /**
     * Set jenisImbalan
     *
     * @param \Fast\SisdikBundle\Entity\JenisImbalan $jenisImbalan
     * @return ImbalanPendaftaran
     */
    public function setJenisImbalan(\Fast\SisdikBundle\Entity\JenisImbalan $jenisImbalan = null) {
        $this->jenisImbalan = $jenisImbalan;

        return $this;
    }

    /**
     * Get jenisImbalan
     *
     * @return \Fast\SisdikBundle\Entity\JenisImbalan 
     */
    public function getJenisImbalan() {
        return $this->jenisImbalan;
    }

    /**
     * Set gelombang
     *
     * @param \Fast\SisdikBundle\Entity\Gelombang $gelombang
     * @return ImbalanPendaftaran
     */
    public function setGelombang(\Fast\SisdikBundle\Entity\Gelombang $gelombang = null) {
        $this->gelombang = $gelombang;

        return $this;
    }

    /**
     * Get gelombang
     *
     * @return \Fast\SisdikBundle\Entity\Gelombang 
     */
    public function getGelombang() {
        return $this->gelombang;
    }

    /**
     * Set tahunmasuk
     *
     * @param \Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk
     * @return ImbalanPendaftaran
     */
    public function setTahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk = null) {
        $this->tahunmasuk = $tahunmasuk;

        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \Fast\SisdikBundle\Entity\Tahunmasuk 
     */
    public function getTahunmasuk() {
        return $this->tahunmasuk;
    }
}
