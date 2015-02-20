<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pembayaran_rutin")
 * @ORM\Entity
 */
class PembayaranRutin
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
     * @ORM\Column(name="tanggal", type="date", nullable=false)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="nama_biaya", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $namaBiaya;

    /**
     * @ORM\Column(name="nominal_biaya", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var integer
     */
    private $nominalBiaya = 0;

    /**
     * @ORM\Column(name="ada_potongan", type="boolean", nullable=true, options={"default" = 0})
     *
     * @var boolean
     */
    private $adaPotongan = false;

    /**
     * @ORM\Column(name="jenis_potongan", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $jenisPotongan;

    /**
     * @ORM\Column(name="persen_potongan", type="smallint", nullable=false, options={"default" = 0})
     * @Assert\GreaterThanOrEqual(value=0)
     * @Assert\LessThanOrEqual(value=100)
     *
     * @var integer
     */
    private $persenPotongan = 0;

    /**
     * @ORM\Column(name="persen_potongan_dinominalkan", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var integer
     */
    private $persenPotonganDinominalkan = 0;

    /**
     * @ORM\Column(name="nominal_potongan", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var integer
     */
    private $nominalPotongan = 0;

    /**
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    private $waktuSimpan;

    /**
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @var \DateTime
     */
    private $waktuUbah;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranRutin")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @ORM\ManyToOne(targetEntity="BiayaRutin")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="biaya_rutin_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var BiayaRutin
     */
    private $biayaRutin;

    /**
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranRutin", mappedBy="pembayaranRutin", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "DESC"})
     * @Assert\Valid
     *
     * @var ArrayCollection
     */
    private $transaksiPembayaranRutin;

    public function __construct()
    {
        $this->transaksiPembayaranRutin = new ArrayCollection();
    }

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
     * @param string $namaBiaya
     */
    public function setNamaBiaya($namaBiaya)
    {
        $this->namaBiaya = $namaBiaya;
    }

    /**
     * @return string
     */
    public function getNamaBiaya()
    {
        return $this->namaBiaya;
    }

    /**
     * @param integer $nominalBiaya
     */
    public function setNominalBiaya($nominalBiaya)
    {
        $this->nominalBiaya = $nominalBiaya;
    }

    /**
     * @return integer
     */
    public function getNominalBiaya()
    {
        return $this->nominalBiaya;
    }

    /**
     * @param boolean $adaPotongan
     */
    public function setAdaPotongan($adaPotongan)
    {
        $this->adaPotongan = $adaPotongan;
    }

    /**
     * @return boolean
     */
    public function getAdaPotongan()
    {
        return $this->adaPotongan;
    }

    /**
     * @param string $jenisPotongan
     */
    public function setJenisPotongan($jenisPotongan)
    {
        $this->jenisPotongan = $jenisPotongan;
    }

    /**
     * @return string
     */
    public function getJenisPotongan()
    {
        return $this->jenisPotongan;
    }

    /**
     * @param integer $persenPotongan
     */
    public function setPersenPotongan($persenPotongan)
    {
        $this->persenPotongan = intval($persenPotongan);
    }

    /**
     * @return integer
     */
    public function getPersenPotongan()
    {
        return $this->persenPotongan;
    }

    /**
     * @param integer $persenPotonganDinominalkan
     */
    public function setPersenPotonganDinominalkan($persenPotonganDinominalkan)
    {
        $this->persenPotonganDinominalkan = $persenPotonganDinominalkan;
    }

    /**
     * @return integer
     */
    public function getPersenPotonganDinominalkan()
    {
        return $this->persenPotonganDinominalkan;
    }

    /**
     * @param integer $nominalPotongan
     */
    public function setNominalPotongan($nominalPotongan)
    {
        $this->nominalPotongan = $nominalPotongan;
    }

    /**
     * @return integer
     */
    public function getNominalPotongan()
    {
        return $this->nominalPotongan;
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
     * @param \DateTime $waktuSimpan
     */
    public function setWaktuSimpan($waktuSimpan)
    {
        $this->waktuSimpan = $waktuSimpan;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * @param \DateTime $waktuUbah
     */
    public function setWaktuUbah($waktuUbah)
    {
        $this->waktuUbah = $waktuUbah;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuUbah()
    {
        return $this->waktuUbah;
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

    /**
     * @param BiayaRutin $biayaRutin
     */
    public function setBiayaRutin(BiayaRutin $biayaRutin = null)
    {
        $this->biayaRutin = $biayaRutin;
    }

    /**
     * @return BiayaRutin
     */
    public function getBiayaRutin()
    {
        return $this->biayaRutin;
    }

    /**
     * Menentukan transaksi pembayaran rutin.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $transaksiPembayaranRutin
     */
    public function setTransaksiPembayaranRutin($transaksiPembayaranRutin)
    {
        foreach ($transaksiPembayaranRutin as $transaksi) {
            $transaksi->setPembayaranRutin($this);
        }

        $this->transaksiPembayaranRutin = $transaksiPembayaranRutin;
    }

    /**
     * @return ArrayCollection
     */
    public function getTransaksiPembayaranRutin()
    {
        return $this->transaksiPembayaranRutin;
    }

    /**
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranRutin()
    {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranRutin() as $transaksi) {
            $jumlah += $transaksi->getNominalPembayaran();
        }

        return $jumlah;
    }
}
