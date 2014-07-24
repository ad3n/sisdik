<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="siswa_cmp")
 * @ORM\Entity
 */
class SiswaCmp
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
     * @ORM\Column(name="nilai", type="integer", nullable=true)
     *
     * @var integer
     */
    private $nilai;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="JenisNilai")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jenis_nilai_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var JenisNilai
     */
    private $jenisNilai;

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
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $nilai
     */
    public function setNilai($nilai)
    {
        $this->nilai = $nilai;
    }

    /**
     * @return integer
     */
    public function getNilai()
    {
        return $this->nilai;
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
     * @param JenisNilai $jenisNilai
     */
    public function setJenisNilai(JenisNilai $jenisNilai = null)
    {
        $this->jenisNilai = $jenisNilai;
    }

    /**
     * @return JenisNilai
     */
    public function getJenisNilai()
    {
        return $this->jenisNilai;
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
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }
}
