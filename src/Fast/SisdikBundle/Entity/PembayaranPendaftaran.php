<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PembayaranPendaftaran
 *
 * @ORM\Table(name="pembayaran_pendaftaran")
 * @ORM\Entity
 */
class PembayaranPendaftaran
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var array
     *
     * @ORM\Column(name="daftar_biaya_pendaftaran", type="array", nullable=true)
     */
    private $daftarBiayaPendaftaran;

    /**
     * @var integer
     *
     * @ORM\Column(name="nominal_total", type="bigint", nullable=true)
     */
    private $nominalTotal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ada_potongan", type="boolean", nullable=true, options={"default" = 0})
     */
    private $adaPotongan = false;

    /**
     * @var string
     *
     * @ORM\Column(name="jenis_potongan", type="string", length=45, nullable=true)
     */
    private $jenisPotongan;

    /**
     * @var integer
     *
     * @ORM\Column(name="persen_potongan", type="smallint", nullable=true)
     */
    private $persenPotongan;

    /**
     * @var integer
     *
     * @ORM\Column(name="persen_potongan_dinominalkan", type="bigint", nullable=true)
     */
    private $persenPotonganDinominalkan;

    /**
     * @var integer
     *
     * @ORM\Column(name="nominal_potongan", type="bigint", nullable=true)
     */
    private $nominalPotongan;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     */
    private $keterangan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     */
    private $waktuUbah;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * @var \TransaksiPembayaranPendaftaran
     *
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranPendaftaran", mappedBy="pembayaranPendaftaran", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     */
    private $transaksiPembayaranPendaftaran;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->transaksiPembayaranPendaftaran = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set daftarBiayaPendaftaran
     *
     * @param string $daftarBiayaPendaftaran
     * @return PembayaranPendaftaran
     */
    public function setDaftarBiayaPendaftaran($daftarBiayaPendaftaran) {
        $this->daftarBiayaPendaftaran = $daftarBiayaPendaftaran;

        return $this;
    }

    /**
     * Get daftarBiayaPendaftaran
     *
     * @return array
     */
    public function getDaftarBiayaPendaftaran() {
        return $this->daftarBiayaPendaftaran;
    }

    /**
     * Set nominalTotal
     *
     * @param integer $nominalTotal
     * @return PembayaranPendaftaran
     */
    public function setNominalTotal($nominalTotal) {
        $this->nominalTotal = $nominalTotal;

        return $this;
    }

    /**
     * Get nominalTotal
     *
     * @return integer
     */
    public function getNominalTotal() {
        return $this->nominalTotal;
    }

    /**
     * Set adaPotongan
     *
     * @param boolean $adaPotongan
     * @return PembayaranPendaftaran
     */
    public function setAdaPotongan($adaPotongan) {
        $this->adaPotongan = $adaPotongan;

        return $this;
    }

    /**
     * Get adaPotongan
     *
     * @return boolean
     */
    public function getAdaPotongan() {
        return $this->adaPotongan;
    }

    /**
     * Set jenisPotongan
     *
     * @param string $jenisPotongan
     * @return PembayaranPendaftaran
     */
    public function setJenisPotongan($jenisPotongan) {
        $this->jenisPotongan = $jenisPotongan;

        return $this;
    }

    /**
     * Get jenisPotongan
     *
     * @return string
     */
    public function getJenisPotongan() {
        return $this->jenisPotongan;
    }

    /**
     * Set persenPotongan
     *
     * @param integer $persenPotongan
     * @return PembayaranPendaftaran
     */
    public function setPersenPotongan($persenPotongan) {
        $this->persenPotongan = intval($persenPotongan);

        return $this;
    }

    /**
     * Get persenPotongan
     *
     * @return integer
     */
    public function getPersenPotongan() {
        return $this->persenPotongan;
    }

    /**
     * Set persenPotonganDinominalkan
     *
     * @param integer $persenPotonganDinominalkan
     * @return PembayaranPendaftaran
     */
    public function setPersenPotonganDinominalkan($persenPotonganDinominalkan) {
        $this->persenPotonganDinominalkan = $persenPotonganDinominalkan;

        return $this;
    }

    /**
     * Get persenPotonganDinominalkan
     *
     * @return integer
     */
    public function getPersenPotonganDinominalkan() {
        return $this->persenPotonganDinominalkan;
    }

    /**
     * Set nominalPotongan
     *
     * @param integer $nominalPotongan
     * @return PembayaranPendaftaran
     */
    public function setNominalPotongan($nominalPotongan) {
        $this->nominalPotongan = $nominalPotongan;

        return $this;
    }

    /**
     * Get nominalPotongan
     *
     * @return integer
     */
    public function getNominalPotongan() {
        return $this->nominalPotongan;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return PembayaranPendaftaran
     */
    public function setKeterangan($keterangan) {
        $this->keterangan = $keterangan;

        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string
     */
    public function getKeterangan() {
        return $this->keterangan;
    }

    /**
     * Set waktuSimpan
     *
     * @param \DateTime $waktuSimpan
     * @return PembayaranPendaftaran
     */
    public function setWaktuSimpan($waktuSimpan) {
        $this->waktuSimpan = $waktuSimpan;

        return $this;
    }

    /**
     * Get waktuSimpan
     *
     * @return \DateTime
     */
    public function getWaktuSimpan() {
        return $this->waktuSimpan;
    }

    /**
     * Set waktuUbah
     *
     * @param \DateTime $waktuUbah
     * @return PembayaranPendaftaran
     */
    public function setWaktuUbah($waktuUbah) {
        $this->waktuUbah = $waktuUbah;

        return $this;
    }

    /**
     * Get waktuUbah
     *
     * @return \DateTime
     */
    public function getWaktuUbah() {
        return $this->waktuUbah;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return PembayaranPendaftaran
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null) {
        $this->siswa = $siswa;

        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa
     */
    public function getSiswa() {
        return $this->siswa;
    }

    /**
     * Set transaksiPembayaranPendaftaran
     * parameter type array collection is removed to allow editing
     *
     * @param ArrayCollection $transaksiPembayaranPendaftaran
     */
    public function setTransaksiPembayaranPendaftaran($transaksiPembayaranPendaftaran) {
        foreach ($transaksiPembayaranPendaftaran as $transaksi) {
            $transaksi->setPembayaranPendaftaran($this);
        }

        $this->transaksiPembayaranPendaftaran = $transaksiPembayaranPendaftaran;
    }

    /**
     * Get transaksiPembayaranPendaftaran
     *
     * @return \Fast\SisdikBundle\TransaksiPembayaranPendaftaran
     */
    public function getTransaksiPembayaranPendaftaran() {
        return $this->transaksiPembayaranPendaftaran;
    }

    /**
     * Get total nominal transaksi pembayaran pendaftaran
     *
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranPendaftaran() {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranPendaftaran() as $transaksi) {
            $jumlah += $transaksi->getNominalPembayaran();
        }

        return $jumlah;
    }

    /**
     * Get total nominal transaksi pembayaran pendaftaran hingga transaksi terpilih
     *
     * @param array $nomorTransaksi
     * @return int
     */
    public function getTotalNominalTransaksiPembayaranPendaftaranHinggaTransaksiTerpilih($nomorTransaksi) {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranPendaftaran() as $transaksi) {
            if (array_key_exists($transaksi->getNomorTransaksi(), $nomorTransaksi)) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }
}
