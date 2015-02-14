<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pembayaran_sekali")
 * @ORM\Entity
 */
class PembayaranSekali
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
     * @ORM\Column(name="nominal_total_transaksi", type="bigint", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $nominalTotalTransaksi = 0;

    /**
     * @ORM\Column(name="nominal_total_biaya", type="bigint", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $nominalTotalBiaya = 0;

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
    private $persenPotongan;

    /**
     * @ORM\Column(name="persen_potongan_dinominalkan", type="bigint", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $persenPotonganDinominalkan = 0;

    /**
     * @ORM\Column(name="nominal_potongan", type="bigint", nullable=false, options={"default" = 0})
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
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranSekali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranSekali", mappedBy="pembayaranSekali", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     *
     * @var TransaksiPembayaranSekali
     */
    private $transaksiPembayaranSekali;

    /**
     * @ORM\OneToMany(targetEntity="DaftarBiayaSekali", mappedBy="pembayaranSekali", cascade={"persist"})
     * @ORM\OrderBy({"nama" = "ASC"})
     * @Assert\Valid
     *
     * @var DaftarBiayaSekali
     */
    private $daftarBiayaSekali;

    public function __construct()
    {
        $this->transaksiPembayaranSekali = new ArrayCollection();
        $this->daftarBiayaSekali = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $nominalTotalTransaksi
     */
    public function setNominalTotalTransaksi($nominalTotalTransaksi)
    {
        $this->nominalTotalTransaksi = $nominalTotalTransaksi;
    }

    /**
     * @return integer
     */
    public function getNominalTotalTransaksi()
    {
        return $this->nominalTotalTransaksi;
    }

    /**
     * @param integer $nominalTotalBiaya
     */
    public function setNominalTotalBiaya($nominalTotalBiaya)
    {
        $this->nominalTotalBiaya = $nominalTotalBiaya;
    }

    /**
     * @return integer
     */
    public function getNominalTotalBiaya()
    {
        return $this->nominalTotalBiaya;
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
        $this->persenPotongan = $persenPotongan;
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
     * Menentukan transaksi pembayaran sekali.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $transaksiPembayaranSekali
     */
    public function setTransaksiPembayaranSekali($transaksiPembayaranSekali)
    {
        foreach ($transaksiPembayaranSekali as $transaksi) {
            $transaksi->setPembayaranSekali($this);
        }

        $this->transaksiPembayaranSekali = $transaksiPembayaranSekali;
    }

    /**
     * @return ArrayCollection TransaksiPembayaranSekali
     */
    public function getTransaksiPembayaranSekali()
    {
        return $this->transaksiPembayaranSekali;
    }

    /**
     * Menentukan daftar biaya sekali.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $daftarBiayaSekali
     */
    public function setDaftarBiayaSekali($daftarBiayaSekali)
    {
        foreach ($daftarBiayaSekali as $transaksi) {
            $transaksi->setPembayaranSekali($this);
        }

        $this->daftarBiayaSekali = $daftarBiayaSekali;
    }

    /**
     * @return ArrayCollection DaftarBiayaSekali
     */
    public function getDaftarBiayaSekali()
    {
        return $this->daftarBiayaSekali;
    }

    /**
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranSekali()
    {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranSekali() as $transaksi) {
            $jumlah += $transaksi->getNominalPembayaran();
        }

        return $jumlah;
    }

    /**
     * Mengambil total nominal transaksi pembayaran sekali hingga transaksi terpilih.
     *
     * @param  array $nomorTransaksi
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranSekaliHinggaTransaksiTerpilih($nomorTransaksi)
    {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranSekali() as $transaksi) {
            if (array_key_exists($transaksi->getNomorTransaksi(), $nomorTransaksi)) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }
}
