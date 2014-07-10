<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="kehadiran_siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="siswa_UNIQUE1", columns={"siswa_id", "tanggal"})
 * })
 * @ORM\Entity
 */
class KehadiranSiswa
{
    /**
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="status_kehadiran", type="string", length=100, nullable=false)
     *
     * @var string
     */
    private $statusKehadiran;

    /**
     * @ORM\Column(name="permulaan", type="boolean", nullable=false, options={"default"=1})
     *
     * @var boolean
     */
    private $permulaan = true;

    /**
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="jam", type="string", length=10, nullable=true)
     *
     * @var string
     */
    private $jam;

    /**
     * @ORM\Column(name="sms_dlr", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $smsDlr;

    /**
     * @ORM\Column(name="sms_dlrtime", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $smsDlrtime;

    /**
     * @ORM\Column(name="sms_terproses", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $smsTerproses = false;

    /**
     * @ORM\Column(name="keterangan_status", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $keteranganStatus;

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
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $statusKehadiran
     */
    public function setStatusKehadiran($statusKehadiran)
    {
        $this->statusKehadiran = $statusKehadiran;
    }

    /**
     * @return string
     */
    public function getStatusKehadiran()
    {
        return $this->statusKehadiran;
    }

    /**
     * @param boolean $permulaan
     */
    public function setPermulaan($permulaan)
    {
        $this->permulaan = $permulaan;
    }

    /**
     * @return boolean
     */
    public function isPermulaan()
    {
        return $this->permulaan;
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
     * @param string $jam
     */
    public function setJam($jam)
    {
        $this->jam = $jam;
    }

    /**
     * @param  boolean $withsecond
     * @return string
     */
    public function getJam($withsecond = TRUE)
    {
        return !$withsecond ? substr($this->jam, 0, 5) : $this->jam;
    }

    /**
     * @param integer $smsDlr
     */
    public function setSmsDlr($smsDlr)
    {
        $this->smsDlr = $smsDlr;
    }

    /**
     * @return integer
     */
    public function getSmsDlr()
    {
        return $this->smsDlr;
    }

    /**
     * @param \DateTime $smsDlrtime
     */
    public function setSmsDlrtime($smsDlrtime)
    {
        $this->smsDlrtime = $smsDlrtime;
    }

    /**
     * @return \DateTime
     */
    public function getSmsDlrtime()
    {
        return $this->smsDlrtime;
    }

    /**
     * @param boolean $smsTerproses
     */
    public function setSmsTerproses($smsTerproses)
    {
        $this->smsTerproses = $smsTerproses;
    }

    /**
     * @return boolean
     */
    public function isSmsTerproses()
    {
        return $this->smsTerproses;
    }

    /**
     * @param string $keteranganStatus
     */
    public function setKeteranganStatus($keteranganStatus)
    {
        $this->keteranganStatus = $keteranganStatus;
    }

    /**
     * @return string
     */
    public function getKeteranganStatus()
    {
        return $this->keteranganStatus;
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
}
