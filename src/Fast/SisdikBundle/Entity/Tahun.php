<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Tahun
 *
 * @ORM\Table(name="tahun", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UNIQ_tahun1", columns={"sekolah_id", "tahun"})
 * })
 * @ORM\Entity
 */
class Tahun
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
     * @var \DateTime
     *
     * @ORM\Column(name="tahun", type="string", nullable=false)
     */
    private $tahun;

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
     * @var \PanitiaPendaftaran
     *
     * @ORM\OneToMany(targetEntity="PanitiaPendaftaran", mappedBy="tahun")
     */
    private $panitiaPendaftaran;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->panitiaPendaftaran = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set tahun
     *
     * @param \DateTime $tahun
     * @return Tahun
     */
    public function setTahun($tahun) {
        $this->tahun = $tahun;

        return $this;
    }

    /**
     * Get tahun
     *
     * @return \DateTime
     */
    public function getTahun() {
        return $this->tahun;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return Tahun
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

    /**
     * Get panitiaPendaftaran
     *
     * @return \Fast\SisdikBundle\PanitiaPendaftaran
     */
    public function getPanitiaPendaftaran() {
        return $this->panitiaPendaftaran;
    }
}
