<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * JadwalKehadiranKepulangan
 *
 * @ORM\Table(name="jadwal_kehadiran_kepulangan")
 * @ORM\Entity
 */
class JadwalKehadiranKepulangan
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
     * @ORM\Column(name="kirim_sms_realtime", type="boolean", nullable=false)
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
     * @ORM\Column(name="kirim_sms_massal", type="boolean", nullable=false)
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
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;

    /**
     * @var \StatusKehadiranKepulangan
     *
     * @ORM\ManyToOne(targetEntity="StatusKehadiranKepulangan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idstatus_kehadiran_kepulangan", referencedColumnName="id")
     * })
     */
    private $idstatusKehadiranKepulangan;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtahun", referencedColumnName="id")
     * })
     */
    private $idtahun;

    /**
     * @var \Templatesms
     *
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idtemplatesms", referencedColumnName="id")
     * })
     */
    private $idtemplatesms;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set perulangan
     *
     * @param string $perulangan
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
    public function getSmsRealtimeHinggaJam() {
        return $this->smsRealtimeHinggaJam;
    }

    /**
     * Set kirimSmsRealtime
     *
     * @param boolean $kirimSmsRealtime
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * @return JadwalKehadiranKepulangan
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
     * Set idkelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return JadwalKehadiranKepulangan
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null) {
        $this->idkelas = $idkelas;

        return $this;
    }

    /**
     * Get idkelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas() {
        return $this->idkelas;
    }

    /**
     * Set idstatusKehadiranKepulangan
     *
     * @param \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan
     * @return JadwalKehadiranKepulangan
     */
    public function setIdstatusKehadiranKepulangan(
            \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan = null) {
        $this->idstatusKehadiranKepulangan = $idstatusKehadiranKepulangan;

        return $this;
    }

    /**
     * Get idstatusKehadiranKepulangan
     *
     * @return \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan 
     */
    public function getIdstatusKehadiranKepulangan() {
        return $this->idstatusKehadiranKepulangan;
    }

    /**
     * Set idtahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $idtahun
     * @return JadwalKehadiranKepulangan
     */
    public function setIdtahun(\Fast\SisdikBundle\Entity\Tahun $idtahun = null) {
        $this->idtahun = $idtahun;

        return $this;
    }

    /**
     * Get idtahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun 
     */
    public function getIdtahun() {
        return $this->idtahun;
    }

    /**
     * Set idtemplatesms
     *
     * @param \Fast\SisdikBundle\Entity\Templatesms $idtemplatesms
     * @return JadwalKehadiranKepulangan
     */
    public function setIdtemplatesms(\Fast\SisdikBundle\Entity\Templatesms $idtemplatesms = null) {
        $this->idtemplatesms = $idtemplatesms;

        return $this;
    }

    /**
     * Get idtemplatesms
     *
     * @return \Fast\SisdikBundle\Entity\Templatesms 
     */
    public function getIdtemplatesms() {
        return $this->idtemplatesms;
    }
}
