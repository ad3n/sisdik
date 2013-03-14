<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalonTransaksiPembayaranSekali
 *
 * @ORM\Table(name="calon_transaksi_pembayaran_sekali")
 * @ORM\Entity
 */
class CalonTransaksiPembayaranSekali
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
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \CalonPembayaranSekali
     *
     * @ORM\ManyToOne(targetEntity="CalonPembayaranSekali", inversedBy="calonTransaksiPembayaranSekali")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="calon_pembayaran_sekali_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $calonPembayaranSekali;

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
     * @return CalonTransaksiPembayaranSekali
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
     * @return CalonTransaksiPembayaranSekali
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
     * Set calonPembayaranSekali
     *
     * @param \Fast\SisdikBundle\Entity\CalonPembayaranSekali $calonPembayaranSekali
     * @return CalonTransaksiPembayaranSekali
     */
    public function setCalonPembayaranSekali(
            \Fast\SisdikBundle\Entity\CalonPembayaranSekali $calonPembayaranSekali = null) {
        $this->calonPembayaranSekali = $calonPembayaranSekali;

        return $this;
    }

    /**
     * Get calonPembayaranSekali
     *
     * @return \Fast\SisdikBundle\Entity\CalonPembayaranSekali
     */
    public function getCalonPembayaranSekali() {
        return $this->calonPembayaranSekali;
    }
}
