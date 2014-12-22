<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="transaksi_pembayaran_sekali")
 * @ORM\Entity
 */
class TransaksiPembayaranSekali
{
    const tandakwitansi = 'S';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var integer
     */
    private $nominalPembayaran = 0;

    /**
     * @ORM\Column(name="nomor_urut_transaksi_perbulan", type="smallint", nullable=true, options={"unsigned"=true})
     *
     * @var integer
     */
    private $nomorUrutTransaksiPerbulan;

    /**
     * @ORM\Column(name="nomor_transaksi", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $nomorTransaksi;

    /**
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    private $waktuSimpan;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @ORM\ManyToOne(targetEntity="PembayaranSekali", inversedBy="transaksiPembayaranSekali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="pembayaran_sekali_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var PembayaranSekali
     */
    private $pembayaranSekali;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="dibuat_oleh_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var User
     */
    private $dibuatOleh;

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
     * @param integer $nomorUrutTransaksiPerbulan
     */
    public function setNomorUrutTransaksiPerbulan($nomorUrutTransaksiPerbulan)
    {
        $this->nomorUrutTransaksiPerbulan = $nomorUrutTransaksiPerbulan;
    }

    /**
     * @return integer
     */
    public function getNomorUrutTransaksiPerbulan()
    {
        return $this->nomorUrutTransaksiPerbulan;
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
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }

    /**
     * @param PembayaranSekali $pembayaranSekali
     */
    public function setPembayaranSekali(PembayaranSekali $pembayaranSekali = null)
    {
        $this->pembayaranSekali = $pembayaranSekali;
    }

    /**
     * @return PembayaranSekali
     */
    public function getPembayaranSekali()
    {
        return $this->pembayaranSekali;
    }

    /**
     * @param User $dibuatOleh
     */
    public function setDibuatOleh(User $dibuatOleh = null)
    {
        $this->dibuatOleh = $dibuatOleh;
    }

    /**
     * @return User
     */
    public function getDibuatOleh()
    {
        return $this->dibuatOleh;
    }
}
