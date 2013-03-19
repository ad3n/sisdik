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
     * @return \Fast\SisdikBundle\TransaksiPembayaranPendaftaran
     */
    public function getTotalNominalTransaksiPembayaranPendaftaran() {
        $jumlah = 0;

        foreach ($this->getTransaksiPembayaranPendaftaran() as $transaksi) {
            $jumlah += $transaksi->getNominalPembayaran();
        }

        return $jumlah;
    }
}
