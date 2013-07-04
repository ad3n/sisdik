<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * JadwalKehadiran
 *
 * @ORM\Table(name="jadwal_kehadiran")
 * @ORM\Entity
 */
class JadwalKehadiran
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
     * @ORM\Column(name="status_kehadiran", type="string", length=100, nullable=false)
     */
    private $statusKehadiran;

    /**
     * @var string
     *
     * @ORM\Column(name="perulangan", type="string", nullable=true)
     */
    private $perulangan;

    /**
     * @var integer
     *
     * @ORM\Column(name="mingguan_hari_ke", type="smallint", nullable=true)
     */
    private $mingguanHariKe;

    /**
     * @var integer
     *
     * @ORM\Column(name="bulanan_hari_ke", type="smallint", nullable=true)
     */
    private $bulananHariKe;

    /**
     * @var string
     *
     * @ORM\Column(name="paramstatus_dari_jam", type="string", length=50, nullable=false)
     */
    private $paramstatusDariJam;

    /**
     * @var string
     *
     * @ORM\Column(name="paramstatus_hingga_jam", type="string", length=50, nullable=false)
     */
    private $paramstatusHinggaJam;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_realtime_dari_jam", type="string", length=50, nullable=false)
     */
    private $smsRealtimeDariJam;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_realtime_hingga_jam", type="string", length=50, nullable=false)
     */
    private $smsRealtimeHinggaJam;

    /**
     * @var boolean
     *
     * @ORM\Column(name="kirim_sms_realtime", type="boolean", nullable=false, options={"default"=0})
     */
    private $kirimSmsRealtime;

    /**
     * @var string
     *
     * @ORM\Column(name="command_realtime", type="string", length=100, nullable=true)
     */
    private $commandRealtime;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_massal_jam", type="string", length=50, nullable=false)
     */
    private $smsMassalJam;

    /**
     * @var boolean
     *
     * @ORM\Column(name="kirim_sms_massal", type="boolean", nullable=false, options={"default"=0})
     */
    private $kirimSmsMassal;

    /**
     * @var string
     *
     * @ORM\Column(name="command_massal", type="string", length=100, nullable=true)
     */
    private $commandMassal;

    /**
     * @var string
     *
     * @ORM\Column(name="dari_jam", type="string", length=50, nullable=false)
     */
    private $dariJam;

    /**
     * @var string
     *
     * @ORM\Column(name="hingga_jam", type="string", length=50, nullable=true)
     */
    private $hinggaJam;

    /**
     * @var string
     *
     * @ORM\Column(name="command_jadwal", type="string", length=100, nullable=true)
     */
    private $commandJadwal;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $sekolah;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $kelas;

    /**
     * @var \TahunAkademik
     *
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunAkademik;

    /**
     * @var \Templatesms
     *
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="templatesms_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $templatesms;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set statusKehadiran
     *
     * @param string $statusKehadiran
     * @return KehadiranSiswa
     */
    public function setStatusKehadiran($statusKehadiran) {
        $this->statusKehadiran = $statusKehadiran;

        return $this;
    }

    /**
     * Get statusKehadiran
     *
     * @return string
     */
    public function getStatusKehadiran() {
        return $this->statusKehadiran;
    }

    /**
     * Set perulangan
     *
     * @param string $perulangan
     * @return JadwalKehadiran
     */
    public function setPerulangan($perulangan) {
        $this->perulangan = $perulangan;

        return $this;
    }

    /**
     * Get perulangan
     *
     * @return string
     */
    public function getPerulangan() {
        return $this->perulangan;
    }

    /**
     * Set mingguanHariKe
     *
     * @param integer $mingguanHariKe
     * @return JadwalKehadiran
     */
    public function setMingguanHariKe($mingguanHariKe) {
        $this->mingguanHariKe = $mingguanHariKe;

        return $this;
    }

    /**
     * Get mingguanHariKe
     *
     * @return integer
     */
    public function getMingguanHariKe() {
        return $this->mingguanHariKe;
    }

    /**
     * Set bulananHariKe
     *
     * @param integer $bulananHariKe
     * @return JadwalKehadiran
     */
    public function setBulananHariKe($bulananHariKe) {
        $this->bulananHariKe = $bulananHariKe;

        return $this;
    }

    /**
     * Get bulananHariKe
     *
     * @return integer
     */
    public function getBulananHariKe() {
        return $this->bulananHariKe;
    }

    /**
     * Set paramstatusDariJam
     *
     * @param string $paramstatusDariJam
     * @return JadwalKehadiran
     */
    public function setParamstatusDariJam($paramstatusDariJam) {
        $this->paramstatusDariJam = $paramstatusDariJam;

        return $this;
    }

    /**
     * Get paramstatusDariJam
     *
     * @param boolean $withsecond
     * @return string
     */
    public function getParamstatusDariJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->paramstatusDariJam, 0, 5);
        } else {
            return $this->paramstatusDariJam;
        }
    }

    /**
     * Set paramstatusHinggaJam
     *
     * @param string $paramstatusHinggaJam
     * @return JadwalKehadiran
     */
    public function setParamstatusHinggaJam($paramstatusHinggaJam) {
        $this->paramstatusHinggaJam = $paramstatusHinggaJam;

        return $this;
    }

    /**
     * Get paramstatusHinggaJam
     *
     * @return string
     */
    public function getParamstatusHinggaJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->paramstatusHinggaJam, 0, 5);
        } else {
            return $this->paramstatusHinggaJam;
        }
    }

    /**
     * Set smsRealtimeDariJam
     *
     * @param string $smsRealtimeDariJam
     * @return JadwalKehadiran
     */
    public function setSmsRealtimeDariJam($smsRealtimeDariJam) {
        $this->smsRealtimeDariJam = $smsRealtimeDariJam;

        return $this;
    }

    /**
     * Get smsRealtimeDariJam
     *
     * @return string
     */
    public function getSmsRealtimeDariJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->smsRealtimeDariJam, 0, 5);
        } else {
            return $this->smsRealtimeDariJam;
        }
    }

    /**
     * Set smsRealtimeHinggaJam
     *
     * @param string $smsRealtimeHinggaJam
     * @return JadwalKehadiran
     */
    public function setSmsRealtimeHinggaJam($smsRealtimeHinggaJam) {
        $this->smsRealtimeHinggaJam = $smsRealtimeHinggaJam;

        return $this;
    }

    /**
     * Get smsRealtimeHinggaJam
     *
     * @return string
     */
    public function getSmsRealtimeHinggaJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->smsRealtimeHinggaJam, 0, 5);
        } else {
            return $this->smsRealtimeHinggaJam;
        }
    }

    /**
     * Set kirimSmsRealtime
     *
     * @param boolean $kirimSmsRealtime
     * @return JadwalKehadiran
     */
    public function setKirimSmsRealtime($kirimSmsRealtime) {
        $this->kirimSmsRealtime = $kirimSmsRealtime;

        return $this;
    }

    /**
     * Get kirimSmsRealtime
     *
     * @return boolean
     */
    public function getKirimSmsRealtime() {
        return $this->kirimSmsRealtime;
    }

    /**
     * Set commandRealtime
     *
     * @param string $commandRealtime
     * @return JadwalKehadiran
     */
    public function setCommandRealtime($commandRealtime) {
        $this->commandRealtime = $commandRealtime;

        return $this;
    }

    /**
     * Get commandRealtime
     *
     * @return string
     */
    public function getCommandRealtime() {
        return $this->commandRealtime;
    }

    /**
     * Set smsMassalJam
     *
     * @param string $smsMassalJam
     * @return JadwalKehadiran
     */
    public function setSmsMassalJam($smsMassalJam) {
        $this->smsMassalJam = $smsMassalJam;

        return $this;
    }

    /**
     * Get smsMassalJam
     *
     * @return string
     */
    public function getSmsMassalJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->smsMassalJam, 0, 5);
        } else {
            return $this->smsMassalJam;
        }
    }

    /**
     * Set kirimSmsMassal
     *
     * @param boolean $kirimSmsMassal
     * @return JadwalKehadiran
     */
    public function setKirimSmsMassal($kirimSmsMassal) {
        $this->kirimSmsMassal = $kirimSmsMassal;

        return $this;
    }

    /**
     * Get kirimSmsMassal
     *
     * @return boolean
     */
    public function getKirimSmsMassal() {
        return $this->kirimSmsMassal;
    }

    /**
     * Set commandMassal
     *
     * @param string $commandMassal
     * @return JadwalKehadiran
     */
    public function setCommandMassal($commandMassal) {
        $this->commandMassal = $commandMassal;

        return $this;
    }

    /**
     * Get commandMassal
     *
     * @return string
     */
    public function getCommandMassal() {
        return $this->commandMassal;
    }

    /**
     * Set dariJam
     *
     * @param string $dariJam
     * @return JadwalKehadiran
     */
    public function setDariJam($dariJam) {
        $this->dariJam = $dariJam;

        return $this;
    }

    /**
     * Get dariJam
     *
     * @return string
     */
    public function getDariJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->dariJam, 0, 5);
        } else {
            return $this->dariJam;
        }
    }

    /**
     * Set hinggaJam
     *
     * @param string $hinggaJam
     * @return JadwalKehadiran
     */
    public function setHinggaJam($hinggaJam) {
        $this->hinggaJam = $hinggaJam;

        return $this;
    }

    /**
     * Get hinggaJam
     *
     * @return string
     */
    public function getHinggaJam($withsecond = FALSE) {
        if (!$withsecond) {
            return substr($this->hinggaJam, 0, 5);
        } else {
            return $this->hinggaJam;
        }
    }

    /**
     * Set commandJadwal
     *
     * @param string $commandJadwal
     * @return JadwalKehadiran
     */
    public function setCommandJadwal($commandJadwal) {
        $this->commandJadwal = $commandJadwal;

        return $this;
    }

    /**
     * Get commandJadwal
     *
     * @return string
     */
    public function getCommandJadwal() {
        return $this->commandJadwal;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return JadwalKehadiran
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
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return JadwalKehadiran
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

    /**
     * Set tahunAkademik
     *
     * @param \Fast\SisdikBundle\Entity\TahunAkademik $tahunAkademik
     * @return JadwalKehadiran
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
     * Set templatesms
     *
     * @param \Fast\SisdikBundle\Entity\Templatesms $templatesms
     * @return JadwalKehadiran
     */
    public function setTemplatesms(\Fast\SisdikBundle\Entity\Templatesms $templatesms = null) {
        $this->templatesms = $templatesms;

        return $this;
    }

    /**
     * Get templatesms
     *
     * @return \Fast\SisdikBundle\Entity\Templatesms
     */
    public function getTemplatesms() {
        return $this->templatesms;
    }
}
