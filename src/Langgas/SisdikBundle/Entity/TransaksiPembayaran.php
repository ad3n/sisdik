<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="view_transaksi_pembayaran")
 * @ORM\Entity(readOnly=true)
 */
class TransaksiPembayaran
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
     * @ORM\Column(name="nominal_pembayaran", type="bigint", nullable=false, options={"default" = 0})
     *
     * @var integer
     */
    private $nominalPembayaran;

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
     * @ORM\ManyToOne(targetEntity="PembayaranPendaftaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="pembayaran_pendaftaran_id", referencedColumnName="id")
     * })
     *
     * @var PembayaranPendaftaran
     */
    private $pembayaranPendaftaran;

    /**
     * @ORM\ManyToOne(targetEntity="PembayaranSekali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="pembayaran_sekali_id", referencedColumnName="id")
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
     * @return integer
     */
    public function getNominalPembayaran()
    {
        return $this->nominalPembayaran;
    }

    /**
     * @return string
     */
    public function getNomorTransaksi()
    {
        return $this->nomorTransaksi;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }

    /**
     * @return PembayaranPendaftaran
     */
    public function getPembayaranPendaftaran()
    {
        if ($this->pembayaranPendaftaran instanceof PembayaranPendaftaran) {
            return $this->pembayaranPendaftaran;
        } else {
            return 0;
        }
    }

    /**
     * @return PembayaranSekali
     */
    public function getPembayaranSekali()
    {
        return $this->pembayaranSekali;
    }

    /**
     * @return User
     */
    public function getDibuatOleh()
    {
        return $this->dibuatOleh;
    }
}
