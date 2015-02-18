<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="biaya_rutin")
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
     * @ORM\Column(name="nominal", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     * @Assert\Length(min=4)
     *
     * @var integer
     */
    private $nominal;

    /**
     * @var integer
     */
    private $nominalSebelumnya;

    /**
     * @ORM\Column(name="perulangan", type="string", length=100, nullable=false)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $perulangan;

    /**
     * @ORM\Column(name="mingguan_hari_ke", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $mingguanHariKe;

    /**
     * @ORM\Column(name="bulanan_hari_ke", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $bulananHariKe;

    /**
     * @ORM\Column(name="bulan_awal", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $bulanAwal;

    /**
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $urutan;

    /**
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull
     *
     * @var Tahun
     */
    private $tahun;

    /**
     * @ORM\ManyToOne(targetEntity="Penjurusan")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="penjurusan_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Penjurusan
     */
    private $penjurusan;

    /**
     * @ORM\ManyToOne(targetEntity="Jenisbiaya")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jenisbiaya_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull
     *
     * @var Jenisbiaya
     */
    private $jenisbiaya;

    /**
     * Daftar perulangan yang mungkin digunakan untuk biaya berulang
     *
     * @return array
     */
    public static function getDaftarPerulangan()
    {
        return [
            'a-harian' => 'Harian',
            'b-mingguan' => 'Mingguan',
            'c-bulanan' => 'Bulanan',
            'd-triwulan' => 'Triwulan',
            'e-caturwulan' => 'Caturwulan',
            'f-semester' => 'Semester',
            'g-tahunan' => 'Tahunan',
        ];
    }

    /**
     * Daftar nama-nama bulan dalam setahun
     *
     * @return array
     */
    public static function getDaftarNamaBulan()
    {
        return [
            1 => 'label.januari',
            'label.februari',
            'label.maret',
            'label.april',
            'label.mei',
            'label.juni',
            'label.juli',
            'label.agustus',
            'label.september',
            'label.oktober',
            'label.november',
            'label.desember',
        ];
    }

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
     * @param integer $mingguanHariKe
     */
    public function setMingguanHariKe($mingguanHariKe)
    {
        $this->mingguanHariKe = $mingguanHariKe;
    }

    /**
     * @return integer
     */
    public function getMingguanHariKe()
    {
        return $this->mingguanHariKe;
    }

    /**
     * @param integer $bulananHariKe
     */
    public function setBulananHariKe($bulananHariKe)
    {
        $this->bulananHariKe = $bulananHariKe;
    }

    /**
     * @return integer
     */
    public function getBulananHariKe()
    {
        return $this->bulananHariKe;
    }

    /**
     * @param integer $bulanAwal
     */
    public function setBulanAwal($bulanAwal)
    {
        $this->bulanAwal = $bulanAwal;
    }

    /**
     * @return integer
     */
    public function getBulanAwal()
    {
        return $this->bulanAwal;
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
     * @param Penjurusan $penjurusan
     */
    public function setPenjurusan(Penjurusan $penjurusan = null)
    {
        $this->penjurusan = $penjurusan;
    }

    /**
     * @return Penjurusan
     */
    public function getPenjurusan()
    {
        return $this->penjurusan;
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
