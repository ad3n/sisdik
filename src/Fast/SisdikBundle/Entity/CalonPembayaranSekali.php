<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalonPembayaranSekali
 *
 * @ORM\Table(name="calon_pembayaran_sekali", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="calon_siswa_id_UNIQUE", columns={"calon_siswa_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CalonPembayaranSekali
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
     * @var integer
     *
     * @ORM\Column(name="daftar_biaya_sekali", type="text", nullable=true)
     */
    private $daftarBiayaSekali;

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
     * @var \CalonSiswa
     *
     * @ORM\ManyToOne(targetEntity="CalonSiswa", inversedBy="calonPembayaranSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $calonSiswa;

    /**
     * @var \CalonTransaksiPembayaranSekali
     *
     * @ORM\OneToMany(targetEntity="CalonTransaksiPembayaranSekali", mappedBy="calonPembayaranSekali", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     */
    private $calonTransaksiPembayaranSekali;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->calonTransaksiPembayaranSekali = new ArrayCollection();
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
     * Set daftarBiayaSekali
     *
     * @param string $daftarBiayaSekali
     * @return CalonPembayaranSekali
     */
    public function setDaftarBiayaSekali($daftarBiayaSekali) {
        $this->daftarBiayaSekali = serialize($daftarBiayaSekali);

        return $this;
    }

    /**
     * Get daftarBiayaSekali
     *
     * @return string
     */
    public function getDaftarBiayaSekali() {
        if (unserialize($this->daftarBiayaSekali)) {
            return unserialize($this->daftarBiayaSekali);
        } else {
            return array();
        }
    }

    /**
     * Set nominalTotal
     *
     * @param integer $nominalTotal
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * @return CalonPembayaranSekali
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
     * Set calonSiswa
     *
     * @param \Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa
     * @return CalonPembayaranSekali
     */
    public function setCalonSiswa(\Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa = null) {
        $this->calonSiswa = $calonSiswa;

        return $this;
    }

    /**
     * Get calonSiswa
     *
     * @return \Fast\SisdikBundle\Entity\CalonSiswa
     */
    public function getCalonSiswa() {
        return $this->calonSiswa;
    }

    /**
     * Set calonTransaksiPembayaranSekali
     *
     * @param ArrayCollection $calonOrangtuaWali
     */
    public function setCalonTransaksiPembayaranSekali($calonTransaksiPembayaranSekali) {
        foreach ($calonTransaksiPembayaranSekali as $transaksi) {
            $transaksi->setCalonPembayaranSekali($this);
        }

        $this->calonTransaksiPembayaranSekali = $calonTransaksiPembayaranSekali;
    }

    /**
     * Get calonTransaksiPembayaranSekali
     *
     * @return \Fast\SisdikBundle\CalonTransaksiPembayaranSekali
     */
    public function getCalonTransaksiPembayaranSekali() {
        return $this->calonTransaksiPembayaranSekali;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preSave() {
        // ??
    }
}
