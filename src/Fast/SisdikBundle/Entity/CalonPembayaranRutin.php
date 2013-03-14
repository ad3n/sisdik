<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CalonPembayaranRutin
 *
 * @ORM\Table(name="calon_pembayaran_rutin")
 * @ORM\Entity
 */
class CalonPembayaranRutin
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
     * @ORM\Column(name="daftar_biaya_rutin", type="text", nullable=true)
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
     * @ORM\ManyToOne(targetEntity="CalonSiswa", inversedBy="calonPembayaranRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $calonSiswa;

    /**
     * @var \CalonTransaksiPembayaranRutin
     *
     * @ORM\OneToMany(targetEntity="CalonTransaksiPembayaranRutin", mappedBy="calonPembayaranRutin", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     */
    private $calonTransaksiPembayaranRutin;

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->calonTransaksiPembayaranRutin = new ArrayCollection();
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
     * @param string $daftarBiayaRutin
     * @return CalonPembayaranRutin
     */
    public function setDaftarBiayaRutin($daftarBiayaRutin) {
        $this->daftarBiayaRutin = serialize($daftarBiayaRutin);

        return $this;
    }

    /**
     * Get daftarBiayaRutin
     *
     * @return string
     */
    public function getDaftarBiayaRutin() {
        if (unserialize($this->daftarBiayaRutin)) {
            return unserialize($this->daftarBiayaRutin);
        } else {
            return array();
        }
    }

    /**
     * Set nominalTotal
     *
     * @param integer $nominalTotal
     * @return CalonPembayaranRutin
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
     * @return CalonPembayaranRutin
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
     * Set waktuCatat
     *
     * @param \DateTime $waktuCatat
     * @return CalonPembayaranRutin
     */
    public function setWaktuCatat($waktuCatat) {
        $this->waktuCatat = $waktuCatat;

        return $this;
    }

    /**
     * Get waktuCatat
     *
     * @return \DateTime
     */
    public function getWaktuCatat() {
        return $this->waktuCatat;
    }

    /**
     * Set waktuUbah
     *
     * @param \DateTime $waktuUbah
     * @return CalonPembayaranRutin
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
     * Set biayaRutin
     *
     * @param \Fast\SisdikBundle\Entity\BiayaRutin $biayaRutin
     * @return CalonPembayaranRutin
     */
    public function setBiayaRutin(\Fast\SisdikBundle\Entity\BiayaRutin $biayaRutin = null) {
        $this->biayaRutin = $biayaRutin;

        return $this;
    }

    /**
     * Get biayaRutin
     *
     * @return \Fast\SisdikBundle\Entity\BiayaRutin
     */
    public function getBiayaRutin() {
        return $this->biayaRutin;
    }

    /**
     * Set calonSiswa
     *
     * @param \Fast\SisdikBundle\Entity\CalonSiswa $calonSiswa
     * @return CalonPembayaranRutin
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
     * Set calonTransaksiPembayaranRutin
     *
     * @param ArrayCollection $calonOrangtuaWali
     */
    public function setCalonTransaksiPembayaranRutin($calonTransaksiPembayaranRutin) {
        foreach ($calonTransaksiPembayaranRutin as $transaksi) {
            $transaksi->setCalonPembayaranSekali($this);
        }

        $this->calonTransaksiPembayaranRutin = $calonTransaksiPembayaranRutin;
    }

    /**
     * Get calonTransaksiPembayaranRutin
     *
     * @return \Fast\SisdikBundle\CalonTransaksiPembayaranRutin
     */
    public function getCalonTransaksiPembayaranRutin() {
        return $this->calonTransaksiPembayaranRutin;
    }
}
