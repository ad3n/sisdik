<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="biaya_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="biaya_pendaftaran_UNIQUE", columns={"jenisbiaya_id", "tahun_id", "gelombang_id"})
 * })
 * @ORM\Entity
 */
class BiayaPendaftaran
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
     * @ORM\Column(name="nominal", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     * @Assert\Length(min=5)
     *
     * @var integer
     */
    private $nominal;

    /**
     * @var integer
     */
    private $nominalSebelumnya;

    /**
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $urutan;

    /**
     * @ORM\Column(name="terpakai", type="boolean", nullable=false, options={"default" = 0})
     *
     * @var boolean
     */
    private $terpakai = false;

    /**
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Jenisbiaya
     */
    private $jenisbiaya;

    /**
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Tahun
     */
    private $tahun;

    /**
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Gelombang
     */
    private $gelombang;

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
     * @param integer $nominalSebelumnya
     */
    public function setNominalSebelumnya($nominalSebelumnya)
    {
        $this->nominalSebelumnya = $nominalSebelumnya;
    }

    /**
     * @return integer
     */
    public function getNominalSebelumnya()
    {
        return $this->nominalSebelumnya;
    }

    /**
     * @param integer $urutan
     */
    public function setUrutan($urutan)
    {
        $this->urutan = $urutan;
    }

    /**
     * @return integer
     */
    public function getUrutan()
    {
        return $this->urutan;
    }

    /**
     * @param boolean $terpakai
     */
    public function setTerpakai($terpakai)
    {
        $this->terpakai = $terpakai;
    }

    /**
     * @return boolean
     */
    public function isTerpakai()
    {
        return $this->terpakai;
    }

    /**
     * @param Jenisbiaya $jenisbiaya
     */
    public function setJenisbiaya(Jenisbiaya $jenisbiaya = null)
    {
        $this->jenisbiaya = $jenisbiaya;
    }

    /**
     * @return Jenisbiaya
     */
    public function getJenisbiaya()
    {
        return $this->jenisbiaya;
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
}
