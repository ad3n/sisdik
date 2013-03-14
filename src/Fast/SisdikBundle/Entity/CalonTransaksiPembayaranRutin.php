<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalonTransaksiPembayaranRutin
 *
 * @ORM\Table(name="calon_transaksi_pembayaran_rutin")
 * @ORM\Entity
 */
class CalonTransaksiPembayaranRutin
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
     * @var \CalonPembayaranRutin
     *
     * @ORM\ManyToOne(targetEntity="CalonPembayaranRutin", inversedBy="calonTransaksiPembayaranRutin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_pembayaran_rutin_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $calonPembayaranRutin;

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
     * @return CalonTransaksiPembayaranRutin
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
     * @return CalonTransaksiPembayaranRutin
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
     * @return CalonTransaksiPembayaranRutin
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
     * @return CalonTransaksiPembayaranRutin
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
     * Set calonPembayaranRutin
     *
     * @param \Fast\SisdikBundle\Entity\CalonPembayaranRutin $calonPembayaranRutin
     * @return CalonTransaksiPembayaranRutin
     */
    public function setCalonPembayaranRutin(
            \Fast\SisdikBundle\Entity\CalonPembayaranRutin $calonPembayaranRutin = null) {
        $this->calonPembayaranRutin = $calonPembayaranRutin;

        return $this;
    }

    /**
     * Get calonPembayaranRutin
     *
     * @return \Fast\SisdikBundle\Entity\CalonPembayaranRutin
     */
    public function getCalonPembayaranRutin() {
        return $this->calonPembayaranRutin;
    }
}
