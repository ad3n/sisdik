<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * TransaksiPembayaranPendaftaran
 *
 * @ORM\Table(name="transaksi_pembayaran_pendaftaran")
 * @ORM\Entity
 */
class TransaksiPembayaranPendaftaran
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
     * @Assert\NotBlank
     */
    private $nominalPembayaran;

    /**
     * @var integer
     *
     * @ORM\Column(name="nomor_urut_transaksi_perbulan", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $nomorUrutTransaksiPerbulan;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_transaksi", type="string", length=45, nullable=true)
     */
    private $nomorTransaksi;

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
     * @var \PembayaranPendaftaran
     *
     * @ORM\ManyToOne(targetEntity="PembayaranPendaftaran", inversedBy="transaksiPembayaranPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pembayaran_pendaftaran_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $pembayaranPendaftaran;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dibuat_oleh_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $dibuatOleh;

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
     * @return TransaksiPembayaranPendaftaran
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
     * @return TransaksiPembayaranPendaftaran
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
     * @return TransaksiPembayaranPendaftaran
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
     * @return TransaksiPembayaranPendaftaran
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
     * @return TransaksiPembayaranPendaftaran
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
     * Set pembayaranPendaftaran
     *
     * @param \Fast\SisdikBundle\Entity\PembayaranPendaftaran $pembayaranPendaftaran
     * @return TransaksiPembayaranPendaftaran
     */
    public function setPembayaranPendaftaran(
            \Fast\SisdikBundle\Entity\PembayaranPendaftaran $pembayaranPendaftaran = null) {
        $this->pembayaranPendaftaran = $pembayaranPendaftaran;

        return $this;
    }

    /**
     * Get pembayaranPendaftaran
     *
     * @return \Fast\SisdikBundle\Entity\PembayaranPendaftaran
     */
    public function getPembayaranPendaftaran() {
        return $this->pembayaranPendaftaran;
    }

    /**
     * Set dibuatOleh
     *
     * @param \Fast\SisdikBundle\Entity\User $dibuatOleh
     * @return Siswa
     */
    public function setDibuatOleh(\Fast\SisdikBundle\Entity\User $dibuatOleh = null) {
        $this->dibuatOleh = $dibuatOleh;

        return $this;
    }

    /**
     * Get dibuatOleh
     *
     * @return \Fast\SisdikBundle\Entity\User
     */
    public function getDibuatOleh() {
        return $this->dibuatOleh;
    }
}
