<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pembayaran_pendaftaran")
 * @ORM\Entity
 */
class PembayaranPendaftaran
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
    private $nominalTotalTransaksi;

    /**
     * @ORM\Column(name="nominal_total_biaya", type="bigint", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $nominalTotalBiaya;

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
     *
     * @var integer
     */
    private $persenPotongan = 0;

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
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranPendaftaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranPendaftaran", mappedBy="pembayaranPendaftaran", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     *
     * @var TransaksiPembayaranPendaftaran
     */
    private $transaksiPembayaranPendaftaran;

    /**
     * @ORM\OneToMany(targetEntity="DaftarBiayaPendaftaran", mappedBy="pembayaranPendaftaran", cascade={"persist"})
     * @ORM\OrderBy({"nama" = "ASC"})
     * @Assert\Valid
     *
     * @var DaftarBiayaPendaftaran
     */
    private $daftarBiayaPendaftaran;

    public function __construct()
    {
        $this->transaksiPembayaranPendaftaran = new ArrayCollection();
        $this->daftarBiayaPendaftaran = new ArrayCollection();
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
     * Menentukan transaksi pembayaran pendaftaran.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $transaksiPembayaranPendaftaran
     */
    public function setTransaksiPembayaranPendaftaran($transaksiPembayaranPendaftaran)
    {
        foreach ($transaksiPembayaranPendaftaran as $transaksi) {
            $transaksi->setPembayaranPendaftaran($this);
        }

        $this->transaksiPembayaranPendaftaran = $transaksiPembayaranPendaftaran;
    }

    /**
     * @return ArrayCollection TransaksiPembayaranPendaftaran
     */
    public function getTransaksiPembayaranPendaftaran()
    {
        return $this->transaksiPembayaranPendaftaran;
    }

    /**
     * Menentukan daftar biaya pendaftaran.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $daftarBiayaPendaftaran
     */
    public function setDaftarBiayaPendaftaran($daftarBiayaPendaftaran)
    {
        foreach ($daftarBiayaPendaftaran as $transaksi) {
            $transaksi->setPembayaranPendaftaran($this);
        }

        $this->daftarBiayaPendaftaran = $daftarBiayaPendaftaran;
    }

    /**
     * @return ArrayCollection DaftarBiayaPendaftaran
     */
    public function getDaftarBiayaPendaftaran()
    {
        return $this->daftarBiayaPendaftaran;
    }

    /**
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranPendaftaran()
    {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranPendaftaran() as $transaksi) {
            $jumlah += $transaksi->getNominalPembayaran();
        }

        return $jumlah;
    }

    /**
     * Mengambil total nominal transaksi pembayaran pendaftaran hingga transaksi terpilih.
     *
     * @param  array $nomorTransaksi
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranPendaftaranHinggaTransaksiTerpilih($nomorTransaksi)
    {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranPendaftaran() as $transaksi) {
            if (array_key_exists($transaksi->getNomorTransaksi(), $nomorTransaksi)) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }
}
