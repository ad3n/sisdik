<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TransaksiPembayaranSekali
 *
 * @ORM\Table(name="transaksi_pembayaran_sekali")
 * @ORM\Entity
 */
class TransaksiPembayaranSekali
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
     * @var integer
     *
     * @ORM\Column(name="nomor_urut_transaksi_perbulan", type="smallint", nullable=true)
     */
    private $nomorUrutTransaksiPerbulan;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_transaksi", type="string", length=45, nullable=true)
     */
    private $nomorTransaksi;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \PembayaranSekali
     *
     * @ORM\ManyToOne(targetEntity="PembayaranSekali", inversedBy="transaksiPembayaranSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pembayaran_sekali_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $pembayaranSekali;

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
     * @return TransaksiPembayaranSekali
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
     * Set nomorUrutTransaksiPerbulan
     *
     * @param integer $nomorUrutTransaksiPerbulan
     * @return TransaksiPembayaranSekali
     */
    public function setNomorUrutTransaksiPerbulan($nomorUrutTransaksiPerbulan) {
        $this->nomorUrutTransaksiPerbulan = $nomorUrutTransaksiPerbulan;

        return $this;
    }

    /**
     * Get nomorUrutTransaksiPerbulan
     *
     * @return integer
     */
    public function getNomorUrutTransaksiPerbulan() {
        return $this->nomorUrutTransaksiPerbulan;
    }

    /**
     * Set nomorTransaksi
     *
     * @param string $nomorTransaksi
     * @return TransaksiPembayaranSekali
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
     * Set keterangan
     *
     * @param string $keterangan
     * @return TransaksiPembayaranSekali
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
     * @return TransaksiPembayaranSekali
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
     * Set pembayaranSekali
     *
     * @param \Fast\SisdikBundle\Entity\PembayaranSekali $pembayaranSekali
     * @return TransaksiPembayaranSekali
     */
    public function setPembayaranSekali(\Fast\SisdikBundle\Entity\PembayaranSekali $pembayaranSekali = null) {
        $this->pembayaranSekali = $pembayaranSekali;

        return $this;
    }

    /**
     * Get pembayaranSekali
     *
     * @return \Fast\SisdikBundle\Entity\PembayaranSekali
     */
    public function getPembayaranSekali() {
        return $this->pembayaranSekali;
    }
}
