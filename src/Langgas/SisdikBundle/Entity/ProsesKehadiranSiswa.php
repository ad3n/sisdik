<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="proses_kehadiran_siswa")
 * @ORM\Entity
 */
class ProsesKehadiranSiswa
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
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="berhasil_inisiasi", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $berhasilInisiasi = false;

    /**
     * @ORM\Column(name="berhasil_diperbarui_mesin", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $berhasilDiperbaruiMesin = false;

    /**
     * @ORM\Column(name="berhasil_validasi", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $berhasilValidasi = false;

    /**
     * @ORM\Column(name="berhasil_kirim_sms", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $berhasilKirimSms = false;

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
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var TahunAkademik
     */
    private $tahunAkademik;

    /**
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Kelas
     */
    private $kelas;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $tanggal
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    /**
     * @return \DateTime
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * @param boolean $berhasilInisiasi
     */
    public function setBerhasilInisiasi($berhasilInisiasi)
    {
        $this->berhasilInisiasi = $berhasilInisiasi;
    }

    /**
     * @return boolean
     */
    public function isBerhasilInisiasi()
    {
        return $this->berhasilInisiasi;
    }

    /**
     * @param boolean $berhasilDiperbaruiMesin
     */
    public function setBerhasilDiperbaruiMesin($berhasilDiperbaruiMesin)
    {
        $this->berhasilDiperbaruiMesin = $berhasilDiperbaruiMesin;
    }

    /**
     * @return boolean
     */
    public function isBerhasilDiperbaruiMesin()
    {
        return $this->berhasilDiperbaruiMesin;
    }

    /**
     * @param boolean $berhasilValidasi
     */
    public function setBerhasilValidasi($berhasilValidasi)
    {
        $this->berhasilValidasi = $berhasilValidasi;
    }

    /**
     * @return boolean
     */
    public function isBerhasilValidasi()
    {
        return $this->berhasilValidasi;
    }

    /**
     * @param boolean $berhasilKirimSms
     */
    public function setBerhasilKirimSms($berhasilKirimSms)
    {
        $this->berhasilKirimSms = $berhasilKirimSms;
    }

    /**
     * @return boolean
     */
    public function isBerhasilKirimSms()
    {
        return $this->berhasilKirimSms;
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
     * @param TahunAkademik $tahunAkademik
     */
    public function setTahunAkademik(TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    }

    /**
     * @return TahunAkademik
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }

    /**
     * @param Kelas $kelas
     */
    public function setKelas(Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    }

    /**
     * @return Kelas
     */
    public function getKelas()
    {
        return $this->kelas;
    }
}
