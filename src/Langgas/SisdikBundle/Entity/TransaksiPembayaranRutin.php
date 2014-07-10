<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="transaksi_pembayaran_rutin")
 * @ORM\Entity
 */
class TransaksiPembayaranRutin
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=true)
     * @Assert\Length(min=5)
     * @Assert\NotBlank
     *
     * @var integer
     */
    private $nominalPembayaran;

    /**
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="nomor_transaksi", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $nomorTransaksi;

    /**
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    private $waktuSimpan;

    /**
     * @ORM\ManyToOne(targetEntity="PembayaranRutin", inversedBy="transaksiPembayaranRutin")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="pembayaran_rutin_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var PembayaranRutin
     */
    private $pembayaranRutin;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $nominalPembayaran
     */
    public function setNominalPembayaran($nominalPembayaran)
    {
        $this->nominalPembayaran = $nominalPembayaran;
    }

    /**
     * @return integer
     */
    public function getNominalPembayaran()
    {
        return $this->nominalPembayaran;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param string $nomorTransaksi
     */
    public function setNomorTransaksi($nomorTransaksi)
    {
        $this->nomorTransaksi = $nomorTransaksi;
    }

    /**
     * @return string
     */
    public function getNomorTransaksi()
    {
        return $this->nomorTransaksi;
    }

    /**
     * @param \DateTime $waktuSimpan
     */
    public function setWaktuSimpan($waktuSimpan)
    {
        $this->waktuSimpan = $waktuSimpan;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * @param PembayaranRutin $pembayaranRutin
     */
    public function setPembayaranRutin(PembayaranRutin $pembayaranRutin = null)
    {
        $this->pembayaranRutin = $pembayaranRutin;
    }

    /**
     * @return PembayaranRutin
     */
    public function getPembayaranRutin()
    {
        return $this->pembayaranRutin;
    }
}
