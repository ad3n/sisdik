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
     * @ORM\Column(name="perulangan", type="string", length=100, nullable=false)
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
     * @var boolean
     *
     * @ORM\Column(name="kirim_sms", type="boolean", nullable=false, options={"default"=0})
     */
    private $kirimSms = false;

    /**
     * @var string
     *
     * @ORM\Column(name="sms_jam", type="string", length=50, nullable=true)
     */
    private $smsJam;

    /**
     * @var boolean
     *
     * @ORM\Column(name="otomatis_terhubung_mesin", type="boolean", nullable=false, options={"default"=0})
     */
    private $otomatisTerhubungMesin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="permulaan", type="boolean", nullable=false, options={"default"=0})
     */
    private $permulaan = false;

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
     * Daftar status kehadiran yang bisa ada di database
     *
     * @return array
     */
    public static function getDaftarStatusKehadiran() {
        return array(
                'a-hadir-tepat' => 'status.kehadiran.hadir.tepat',
                'b-hadir-telat' => 'status.kehadiran.hadir.telat', 'c-alpa' => 'status.kehadiran.alpa',
                'd-izin' => 'status.kehadiran.izin', 'e-sakit' => 'status.kehadiran.sakit'
        );
    }

    /**
     * Daftar perulangan
     *
     * @return array
     */
    public static function getDaftarPerulangan() {
        return array(
            'a-harian' => 'Harian', 'b-mingguan' => 'Mingguan', 'c-bulanan' => 'Bulanan',
        );
    }

    /**
     * nama hari dalam satu minggu, dimulai dari senin
     *
     * @return array
     */
    public static function getNamaHari() {
        return array(
                0 => 'label.senin', 'label.selasa', 'label.rabu', 'label.kamis', 'label.jumat',
                'label.sabtu', 'label.minggu',
        );
    }

    /**
     * angka-angka hari dalam satu bulan, dari 1
     *
     * @return array
     */
    public static function getAngkaHariSebulan() {
        return array_combine(range(1, 31), range(1, 31));
    }

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
     * @return JadwalKehadiran
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
    public function getParamstatusDariJam($withsecond = TRUE) {
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
    public function getParamstatusHinggaJam($withsecond = TRUE) {
        if (!$withsecond) {
            return substr($this->paramstatusHinggaJam, 0, 5);
        } else {
            return $this->paramstatusHinggaJam;
        }
    }

    /**
     * Set kirimSms
     *
     * @param boolean $kirimSms
     * @return JadwalKehadiran
     */
    public function setKirimSms($kirimSms) {
        $this->kirimSms = $kirimSms;

        return $this;
    }

    /**
     * Get kirimSms
     *
     * @return boolean
     */
    public function isKirimSms() {
        return $this->kirimSms;
    }

    /**
     * Set smsJam
     *
     * @param string $smsJam
     * @return JadwalKehadiran
     */
    public function setSmsJam($smsJam) {
        $this->smsJam = $smsJam;

        return $this;
    }

    /**
     * Get smsJam
     *
     * @return string
     */
    public function getSmsJam($withsecond = TRUE) {
        if (!$withsecond) {
            return substr($this->smsJam, 0, 5);
        } else {
            return $this->smsJam;
        }
    }

    /**
     * Set otomatisTerhubungMesin
     *
     * @param boolean $otomatisTerhubungMesin
     * @return JadwalKehadiran
     */
    public function setOtomatisTerhubungMesin($otomatisTerhubungMesin) {
        $this->otomatisTerhubungMesin = $otomatisTerhubungMesin;

        return $this;
    }

    /**
     * Get otomatisTerhubungMesin
     *
     * @return boolean
     */
    public function isOtomatisTerhubungMesin() {
        return $this->otomatisTerhubungMesin;
    }

    /**
     * Set permulaan
     *
     * @param boolean $permulaan
     * @return JadwalKehadiran
     */
    public function setPermulaan($permulaan) {
        $this->permulaan = $permulaan;

        return $this;
    }

    /**
     * Is permulaan
     *
     * @return boolean
     */
    public function isPermulaan() {
        return $this->permulaan;
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
