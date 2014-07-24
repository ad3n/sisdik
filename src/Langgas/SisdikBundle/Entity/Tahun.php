<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="tahun", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UNIQ_tahun1", columns={"sekolah_id", "tahun"})
 * })
 * @ORM\Entity
 */
class Tahun
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="tahun", type="string", nullable=false)
     *
     * @var string
     */
    private $tahun;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @ORM\OneToMany(targetEntity="PanitiaPendaftaran", mappedBy="tahun")
     *
     * @var PanitiaPendaftaran
     */
    private $panitiaPendaftaran;

    public function __construct()
    {
        $this->panitiaPendaftaran = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $tahun
     */
    public function setTahun($tahun)
    {
        $this->tahun = $tahun;
    }

    /**
     * @return string
     */
    public function getTahun()
    {
        return $this->tahun;
    }

    /**
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }

    /**
     * @return PanitiaPendaftaran
     */
    public function getPanitiaPendaftaran()
    {
        return $this->panitiaPendaftaran;
    }
}
