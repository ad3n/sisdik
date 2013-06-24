<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BiayaPendaftaran
 *
 * @ORM\Table(name="biaya_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="biaya_pendaftaran_UNIQUE", columns={"jenisbiaya_id", "tahun_id", "gelombang_id"})
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
     * @ORM\Column(name="nominal", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     * @Assert\Length(min=5)
     */
    private $nominal;

    /**
     * @var integer
     *
     */
    private $nominalSebelumnya;

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
     * @var \Jenisbiaya
     *
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $jenisbiaya;

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
     * Set nominalSebelumnya
     *
     * @param integer $nominalSebelumnya
     * @return BiayaPendaftaran
     */
    public function setNominalSebelumnya($nominalSebelumnya) {
        $this->nominalSebelumnya = $nominalSebelumnya;

        return $this;
    }

    /**
     * Get nominalSebelumnya
     *
     * @return integer
     */
    public function getNominalSebelumnya() {
        return $this->nominalSebelumnya;
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
     * Set terpakai
     *
     * @param boolean $terpakai
     * @return Tahun
     */
    public function setTerpakai($terpakai) {
        $this->terpakai = $terpakai;

        return $this;
    }

    /**
     * Get terpakai
     *
     * @return boolean
     */
    public function isTerpakai() {
        return $this->terpakai;
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
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return BiayaPendaftaran
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
