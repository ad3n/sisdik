<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="biaya_sekali", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="biaya_sekali_UNIQUE", columns={"jenisbiaya_id", "tahun_id"})
 * })
 * @ORM\Entity
 */
class BiayaSekali
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
