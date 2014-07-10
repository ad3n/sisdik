<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="imbalan_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_imbalan_pendaftaran1", columns={"tahun_id", "gelombang_id", "jenis_imbalan_id"})
 * })
 * @ORM\Entity
 */
class ImbalanPendaftaran
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
     * @ORM\Column(name="nominal", type="integer", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $nominal;

    /**
     * @ORM\ManyToOne(targetEntity="JenisImbalan")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jenis_imbalan_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var JenisImbalan
     */
    private $jenisImbalan;

    /**
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Gelombang
     */
    private $gelombang;

    /**
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Tahun
     */
    private $tahun;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $nominal
     */
    public function setNominal($nominal)
    {
        $this->nominal = $nominal;
    }

    /**
     * @return integer
     */
    public function getNominal()
    {
        return $this->nominal;
    }

    /**
     * @param JenisImbalan $jenisImbalan
     */
    public function setJenisImbalan(JenisImbalan $jenisImbalan = null)
    {
        $this->jenisImbalan = $jenisImbalan;
    }

    /**
     * @return JenisImbalan
     */
    public function getJenisImbalan()
    {
        return $this->jenisImbalan;
    }

    /**
     * @param Gelombang $gelombang
     */
    public function setGelombang(Gelombang $gelombang = null)
    {
        $this->gelombang = $gelombang;
    }

    /**
     * @return Gelombang
     */
    public function getGelombang()
    {
        return $this->gelombang;
    }

    /**
     * @param Tahun $tahun
     */
    public function setTahun(Tahun $tahun = null)
    {
        $this->tahun = $tahun;
    }

    /**
     * @return Tahun
     */
    public function getTahun()
    {
        return $this->tahun;
    }
}
