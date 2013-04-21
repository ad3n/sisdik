<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * BiayaSekali
 *
 * @ORM\Table(name="biaya_sekali", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="biaya_sekali_UNIQUE", columns={"jenisbiaya_id", "tahun_id"})
 * })
 * @ORM\Entity
 */
class BiayaSekali
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
     * @ORM\Column(name="nominal", type="bigint", nullable=true)
     */
    private $nominal;

    /**
     * @var integer
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

    /**
     * @var boolean
     *
     * @ORM\Column(name="terpakai", type="boolean", nullable=false, options={"default" = 0})
     */
    private $terpakai = false;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahun;

    /**
     * @var \Jenisbiaya
     *
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $jenisbiaya;

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
     * @return BiayaSekali
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
     * Set urutan
     *
     * @param integer $urutan
     * @return BiayaSekali
     */
    public function setUrutan($urutan) {
        $this->urutan = $urutan;

        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer
     */
    public function getUrutan() {
        return $this->urutan;
    }

    /**
     * Set terpakai
     *
     * @param boolean $terpakai
     * @return BiayaSekali
     */
    public function setTerpakai($terpakai) {
        $this->terpakai = $terpakai;

        return $this;
    }

    /**
     * Is terpakai
     *
     * @return boolean
     */
    public function isTerpakai() {
        return $this->terpakai;
    }

    /**
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return BiayaSekali
     */
    public function setTahun(\Fast\SisdikBundle\Entity\Tahun $tahun = null) {
        $this->tahun = $tahun;

        return $this;
    }

    /**
     * Get tahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun
     */
    public function getTahun() {
        return $this->tahun;
    }

    /**
     * Set jenisbiaya
     *
     * @param \Fast\SisdikBundle\Entity\Jenisbiaya $jenisbiaya
     * @return BiayaSekali
     */
    public function setJenisbiaya(\Fast\SisdikBundle\Entity\Jenisbiaya $jenisbiaya = null) {
        $this->jenisbiaya = $jenisbiaya;

        return $this;
    }

    /**
     * Get jenisbiaya
     *
     * @return \Fast\SisdikBundle\Entity\Jenisbiaya
     */
    public function getJenisbiaya() {
        return $this->jenisbiaya;
    }
}
