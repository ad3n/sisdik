<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TransaksiPembayaranRutin
 *
 * @ORM\Table(name="transaksi_pembayaran_rutin")
 * @ORM\Entity
 */
class TransaksiPembayaranRutin
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=true)
     * @Assert\Length(min=5)
     * @Assert\NotBlank
     */
    private $nominalPembayaran;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     */
    private $keterangan;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_transaksi", type="string", length=100, nullable=true)
     */
    private $nomorTransaksi;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \PembayaranRutin
     *
     * @ORM\ManyToOne(targetEntity="PembayaranRutin", inversedBy="transaksiPembayaranRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pembayaran_rutin_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $pembayaranRutin;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nominalPembayaran
     *
     * @param integer $nominalPembayaran
     * @return TransaksiPembayaranRutin
     */
    public function setNominalPembayaran($nominalPembayaran) {
        $this->nominalPembayaran = $nominalPembayaran;

        return $this;
    }

    /**
     * Get nominalPembayaran
     *
     * @return integer
     */
    public function getNominalPembayaran() {
        return $this->nominalPembayaran;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return TransaksiPembayaranRutin
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
     * Set nomorTransaksi
     *
     * @param string $nomorTransaksi
     * @return TransaksiPembayaranRutin
     */
    public function setNomorTransaksi($nomorTransaksi) {
        $this->nomorTransaksi = $nomorTransaksi;

        return $this;
    }

    /**
     * Get nomorTransaksi
     *
     * @return string
     */
    public function getNomorTransaksi() {
        return $this->nomorTransaksi;
    }

    /**
     * Set waktuSimpan
     *
     * @param \DateTime $waktuSimpan
     * @return TransaksiPembayaranRutin
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
     * Set pembayaranRutin
     *
     * @param \Fast\SisdikBundle\Entity\PembayaranRutin $pembayaranRutin
     * @return TransaksiPembayaranRutin
     */
    public function setPembayaranRutin(\Fast\SisdikBundle\Entity\PembayaranRutin $pembayaranRutin = null) {
        $this->pembayaranRutin = $pembayaranRutin;

        return $this;
    }

    /**
     * Get pembayaranRutin
     *
     * @return \Fast\SisdikBundle\Entity\PembayaranRutin
     */
    public function getPembayaranRutin() {
        return $this->pembayaranRutin;
    }
}
