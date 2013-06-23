<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PembayaranSekali
 *
 * @ORM\Table(name="pembayaran_sekali")
 * @ORM\Entity
 */
class PembayaranSekali
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
     * @ORM\Column(name="daftar_biaya_sekali", type="array", nullable=true)
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
     * @Gedmo\Timestampable(on="create")
     */
    private $waktuSimpan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $waktuUbah;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * @var \TransaksiPembayaranSekali
     *
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranSekali", mappedBy="pembayaranSekali", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     */
    private $transaksiPembayaranSekali;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->transaksiPembayaranSekali = new ArrayCollection();
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
     * @param array $daftarBiayaSekali
     * @return PembayaranSekali
     */
    public function setDaftarBiayaSekali($daftarBiayaSekali) {
        $this->daftarBiayaSekali = $daftarBiayaSekali;

        return $this;
    }

    /**
     * Get daftarBiayaSekali
     *
     * @return array
     */
    public function getDaftarBiayaSekali() {
        return $this->daftarBiayaSekali;
    }

    /**
     * Set nominalTotal
     *
     * @param integer $nominalTotal
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * @return PembayaranSekali
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
     * Set TransaksiPembayaranSekali
     * parameter type array collection is removed to allow editing
     *
     * @param ArrayCollection $transaksiPembayaranSekali
     */
    public function setTransaksiPembayaranSekali($transaksiPembayaranSekali) {
        foreach ($transaksiPembayaranSekali as $transaksi) {
            $transaksi->setPembayaranSekali($this);
        }

        $this->transaksiPembayaranSekali = $transaksiPembayaranSekali;
    }

    /**
     * Get transaksiPembayaranSekali
     *
     * @return \Fast\SisdikBundle\TransaksiPembayaranSekali
     */
    public function getTransaksiPembayaranSekali() {
        return $this->transaksiPembayaranSekali;
    }
}
