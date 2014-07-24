<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="jadwal_cmp")
 * @ORM\Entity
 */
class JadwalCmp
{
    /**
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="tanggal", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="jam", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $jam;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="guru_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Guru
     */
    private $guru;

    /**
     * @ORM\ManyToOne(targetEntity="CukilMp")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="cukil_mp_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var CukilMp
     */
    private $cukilMp;

    /**
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Kelas
     */
    private $kelas;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $tanggal
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    /**
     * @return \DateTime
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * @param string $jam
     */
    public function setJam($jam)
    {
        $this->jam = $jam;
    }

    /**
     * @return string
     */
    public function getJam()
    {
        return $this->jam;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param Guru $guru
     */
    public function setGuru(Guru $guru = null)
    {
        $this->guru = $guru;
    }

    /**
     * @return Guru
     */
    public function getGuru()
    {
        return $this->guru;
    }

    /**
     * @param CukilMp $cukilMp
     */
    public function setCukilMp(CukilMp $cukilMp = null)
    {
        $this->cukilMp = $cukilMp;
    }

    /**
     * @return CukilMp
     */
    public function getCukilMp()
    {
        return $this->cukilMp;
    }

    /**
     * @param Kelas $kelas
     */
    public function setKelas(Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    }

    /**
     * @return Kelas
     */
    public function getKelas()
    {
        return $this->kelas;
    }
}
