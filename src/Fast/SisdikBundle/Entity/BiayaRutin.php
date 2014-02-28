<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="biaya_rutin", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UNQ_biaya_rutin1", columns={"jenisbiaya_id", "tahun_id", "gelombang_id"})
 * })
 * @ORM\Entity
 */
class BiayaRutin
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
     * @ORM\Column(name="nominal", type="bigint", nullable=true)
     *
     * @var integer
     */
    private $nominal;

    /**
     * @ORM\Column(name="perulangan", type="string", nullable=true, options={"default"="bulan"})
     *
     * @var string
     */
    private $perulangan;

    /**
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $urutan;

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
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Jenisbiaya
     */
    private $jenisbiaya;

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
     * @param string $perulangan
     */
    public function setPerulangan($perulangan)
    {
        $this->perulangan = $perulangan;
    }

    /**
     * @return string
     */
    public function getPerulangan()
    {
        return $this->perulangan;
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
}
