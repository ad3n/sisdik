<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProsesKehadiranSiswa
 *
 * @ORM\Table(name="proses_kehadiran_siswa")
 * @ORM\Entity
 */
class ProsesKehadiranSiswa
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
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     */
    private $tanggal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berhasil_inisiasi", type="boolean", nullable=false, options={"default"=0})
     */
    private $berhasilInisiasi = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berhasil_diperbarui_mesin", type="boolean", nullable=false, options={"default"=0})
     */
    private $berhasilDiperbaruiMesin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berhasil_validasi", type="boolean", nullable=false, options={"default"=0})
     */
    private $berhasilValidasi = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berhasil_kirim_sms_manual", type="boolean", nullable=false, options={"default"=0})
     */
    private $berhasilKirimSmsManual = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="berhasil_kirim_sms_otomatis", type="boolean", nullable=false, options={"default"=0})
     */
    private $berhasilKirimSmsOtomatis = false;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id")
     * })
     */
    private $sekolah;

    /**
     * @var \TahunAkademik
     *
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id")
     * })
     */
    private $tahunAkademik;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id")
     * })
     */
    private $kelas;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set tanggal
     *
     * @param \DateTime $tanggal
     * @return ProsesKehadiranSiswa
     */
    public function setTanggal($tanggal) {
        $this->tanggal = $tanggal;

        return $this;
    }

    /**
     * Get tanggal
     *
     * @return \DateTime
     */
    public function getTanggal() {
        return $this->tanggal;
    }

    /**
     * Set berhasilInisiasi
     *
     * @param boolean $berhasilInisiasi
     * @return ProsesKehadiranSiswa
     */
    public function setBerhasilInisiasi($berhasilInisiasi) {
        $this->berhasilInisiasi = $berhasilInisiasi;

        return $this;
    }

    /**
     * Get berhasilInisiasi
     *
     * @return boolean
     */
    public function isBerhasilInisiasi() {
        return $this->berhasilInisiasi;
    }

    /**
     * Set berhasilDiperbaruiMesin
     *
     * @param boolean $berhasilDiperbaruiMesin
     * @return ProsesKehadiranSiswa
     */
    public function setBerhasilDiperbaruiMesin($berhasilDiperbaruiMesin) {
        $this->berhasilDiperbaruiMesin = $berhasilDiperbaruiMesin;

        return $this;
    }

    /**
     * Get berhasilDiperbaruiMesin
     *
     * @return boolean
     */
    public function isBerhasilDiperbaruiMesin() {
        return $this->berhasilDiperbaruiMesin;
    }

    /**
     * Set berhasilValidasi
     *
     * @param boolean $berhasilValidasi
     * @return ProsesKehadiranSiswa
     */
    public function setBerhasilValidasi($berhasilValidasi) {
        $this->berhasilValidasi = $berhasilValidasi;

        return $this;
    }

    /**
     * Get berhasilValidasi
     *
     * @return boolean
     */
    public function isBerhasilValidasi() {
        return $this->berhasilValidasi;
    }

    /**
     * Set berhasilKirimSmsManual
     *
     * @param boolean $berhasilKirimSmsManual
     * @return ProsesKehadiranSiswa
     */
    public function setBerhasilKirimSmsManual($berhasilKirimSmsManual) {
        $this->berhasilKirimSmsManual = $berhasilKirimSmsManual;

        return $this;
    }

    /**
     * Get berhasilKirimSmsManual
     *
     * @return boolean
     */
    public function isBerhasilKirimSmsManual() {
        return $this->berhasilKirimSmsManual;
    }

    /**
     * Set berhasilKirimSmsOtomatis
     *
     * @param boolean $berhasilKirimSmsOtomatis
     * @return ProsesKehadiranSiswa
     */
    public function setBerhasilKirimSmsOtomatis($berhasilKirimSmsOtomatis) {
        $this->berhasilKirimSmsOtomatis = $berhasilKirimSmsOtomatis;

        return $this;
    }

    /**
     * Get berhasilKirimSmsOtomatis
     *
     * @return boolean
     */
    public function isBerhasilKirimSmsOtomatis() {
        return $this->berhasilKirimSmsOtomatis;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return ProsesKehadiranSiswa
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $this->sekolah = $sekolah;

        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah
     */
    public function getSekolah() {
        return $this->sekolah;
    }

    /**
     * Set tahunAkademik
     *
     * @param \Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @return ProsesKehadiranSiswa
     */
    public function setTahunAkademik(\Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik = null) {
        $this->tahunAkademik = $tahunAkademik;

        return $this;
    }

    /**
     * Get tahunAkademik
     *
     * @return \Fast\SisdikBundle\Entity\TahunAkademik
     */
    public function getTahunAkademik() {
        return $this->tahunAkademik;
    }

    /**
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return ProsesKehadiranSiswa
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null) {
        $this->kelas = $kelas;

        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas
     */
    public function getKelas() {
        return $this->kelas;
    }
}
