<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PembayaranRutin
 *
 * @ORM\Table(name="pembayaran_rutin")
 * @ORM\Entity
 */
class PembayaranRutin
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
     * @ORM\Column(name="daftar_biaya_rutin", type="array", nullable=true)
     */
    private $daftarBiayaRutin;

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
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * @var \TransaksiPembayaranRutin
     *
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranRutin", mappedBy="pembayaranRutin", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     */
    private $transaksiPembayaranRutin;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->transaksiPembayaranRutin = new ArrayCollection();
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
     * Set daftarBiayaRutin
     *
     * @param array $daftarBiayaRutin
     * @return PembayaranRutin
     */
    public function setDaftarBiayaRutin($daftarBiayaRutin) {
        $this->daftarBiayaRutin = $daftarBiayaRutin;

        return $this;
    }

    /**
     * Get daftarBiayaRutin
     *
     * @return array
     */
    public function getDaftarBiayaRutin() {
        return $this->daftarBiayaRutin;
    }

    /**
     * Set nominalTotal
     *
     * @param integer $nominalTotal
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * @return PembayaranRutin
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
     * Set transaksiPembayaranRutin
     *
     * @param ArrayCollection $transaksiPembayaranRutin
     */
    public function setTransaksiPembayaranRutin($transaksiPembayaranRutin) {
        foreach ($transaksiPembayaranRutin as $transaksi) {
            $transaksi->setPembayaranSekali($this);
        }

        $this->transaksiPembayaranRutin = $transaksiPembayaranRutin;
    }

    /**
     * Get transaksiPembayaranRutin
     *
     * @return \Fast\SisdikBundle\TransaksiPembayaranRutin
     */
    public function getTransaksiPembayaranRutin() {
        return $this->transaksiPembayaranRutin;
    }
}
