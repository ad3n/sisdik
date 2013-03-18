<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BiayaPendaftaran
 *
 * @ORM\Table(name="biaya_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="biaya_pendaftaran_UNIQUE", columns={"jenisbiaya_id", "tahunmasuk_id", "gelombang_id"})
 * })
 * @ORM\Entity
 */
class BiayaPendaftaran
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
     * @Assert\NotBlank
     * @Assert\Length(min=5)
     */
    private $nominal;

    /**
     * @var integer
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

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
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunmasuk;

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
     * @return BiayaPendaftaran
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
     * @return BiayaPendaftaran
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
     * Set jenisbiaya
     *
     * @param \Fast\SisdikBundle\Entity\Jenisbiaya $jenisbiaya
     * @return BiayaPendaftaran
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

    /**
     * Set tahunmasuk
     *
     * @param \Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk
     * @return BiayaPendaftaran
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

    /**
     * Set gelombang
     *
     * @param \Fast\SisdikBundle\Entity\Gelombang $gelombang
     * @return BiayaPendaftaran
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
}
