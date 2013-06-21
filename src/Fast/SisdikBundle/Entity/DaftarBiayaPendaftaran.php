<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DaftarBiayaPendaftaran
 *
 * @ORM\Table(name="daftar_biaya_pendaftaran")
 * @ORM\Entity
 */
class DaftarBiayaPendaftaran
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
     * @var string
     *
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     * @Assert\NotBlank
     */
    private $nama;

    /**
     * @var boolean
     */
    private $terpilih;

    /**
     * @var integer
     *
     * @ORM\Column(name="nominal", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     */
    private $nominal;

    /**
     * @var \PembayaranPendaftaran
     *
     * @ORM\ManyToOne(targetEntity="PembayaranPendaftaran", inversedBy="daftarBiayaPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pembayaran_pendaftaran_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $pembayaranPendaftaran;

    /**
     * @var \BiayaPendaftaran
     *
     * @ORM\ManyToOne(targetEntity="BiayaPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="biaya_pendaftaran_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $biayaPendaftaran;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return DaftarBiayaPendaftaran
     */
    public function setNama($nama) {
        $this->nama = $nama;

        return $this;
    }

    /**
     * Get nama
     *
     * @return string
     */
    public function getNama() {
        return $this->nama;
    }

    /**
     * Set terpilih
     *
     * @param boolean $terpilih
     * @return DaftarBiayaPendaftaran
     */
    public function setTerpilih($terpilih) {
        $this->terpilih = $terpilih;

        return $this;
    }

    /**
     * Get terpilih
     *
     * @return boolean
     */
    public function isTerpilih() {
        return $this->terpilih;
    }

    /**
     * Set nominal
     *
     * @param integer $nominal
     * @return DaftarBiayaPendaftaran
     */
    public function setNominal($nominal) {
        $this->nominal = $nominal;

        return $this;
    }

    /**
     * Get nominal
     *
     * @return integer
     */
    public function getNominal() {
        return $this->nominal;
    }

    /**
     * Set pembayaranPendaftaran
     *
     * @param \Fast\SisdikBundle\Entity\PembayaranPendaftaran $pembayaranPendaftaran
     * @return DaftarBiayaPendaftaran
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
     * Set biayaPendaftaran
     *
     * @param \Fast\SisdikBundle\Entity\BiayaPendaftaran $biayaPendaftaran
     * @return DaftarBiayaPendaftaran
     */
    public function setBiayaPendaftaran(\Fast\SisdikBundle\Entity\BiayaPendaftaran $biayaPendaftaran = null) {
        $this->biayaPendaftaran = $biayaPendaftaran;

        return $this;
    }

    /**
     * Get biayaPendaftaran
     *
     * @return \Fast\SisdikBundle\Entity\BiayaPendaftaran
     */
    public function getBiayaPendaftaran() {
        return $this->biayaPendaftaran;
    }
}
