<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="restitusi_pendaftaran")
 * @ORM\Entity
 */
class RestitusiPendaftaran
{
    const tandakwitansi = 'R';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nominal_restitusi", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var integer
     */
    private $nominalRestitusi = 0;

    /**
     * @ORM\Column(name="nomor_urut_transaksi_pertahun", type="smallint", nullable=true, options={"unsigned"=true})
     *
     * @var integer
     */
    private $nomorUrutTransaksiPertahun;

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
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="restitusiPendaftaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

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
     * @param integer $nominalRestitusi
     */
    public function setNominalRestitusi($nominalRestitusi)
    {
        $this->nominalRestitusi = $nominalRestitusi;
    }

    /**
     * @return integer
     */
    public function getNominalRestitusi()
    {
        return $this->nominalRestitusi;
    }

    /**
     * @param integer $nomorUrutTransaksiPertahun
     */
    public function setNomorUrutTransaksiPertahun($nomorUrutTransaksiPertahun)
    {
        $this->nomorUrutTransaksiPertahun = $nomorUrutTransaksiPertahun;
    }

    /**
     * @return integer
     */
    public function getNomorUrutTransaksiPertahun()
    {
        return $this->nomorUrutTransaksiPertahun;
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
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
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
