<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;

/**
 * @ORM\Table(name="jadwal_kehadiran")
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class JadwalKehadiran
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
     * @ORM\Column(name="status_kehadiran", type="string", length=100, nullable=false)
     *
     * @var string
     */
    private $statusKehadiran;

    /**
     * @ORM\Column(name="perulangan", type="string", length=100, nullable=false)
     *
     * @var string
     */
    private $perulangan;

    /**
     * @ORM\Column(name="mingguan_hari_ke", type="smallint", nullable=true)
     * @Expose
     * @SerializedName("hari")
     *
     * @var integer
     */
    private $mingguanHariKe;

    /**
     * @ORM\Column(name="bulanan_hari_ke", type="smallint", nullable=true)
     * @Expose
     * @SerializedName("tanggal")
     *
     * @var integer
     */
    private $bulananHariKe;

    /**
     * @ORM\Column(name="paramstatus_dari_jam", type="string", length=50, nullable=false)
     * @Expose
     * @SerializedName("dari_jam")
     *
     * @var string
     */
    private $paramstatusDariJam;

    /**
     * @ORM\Column(name="paramstatus_hingga_jam", type="string", length=50, nullable=false)
     * @Expose
     * @SerializedName("hingga_jam")
     *
     * @var string
     */
    private $paramstatusHinggaJam;

    /**
     * @ORM\Column(name="kirim_sms", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $kirimSms = false;

    /**
     * @ORM\Column(name="sms_jam", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $smsJam;

    /**
     * @ORM\Column(name="otomatis_terhubung_mesin", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $otomatisTerhubungMesin = false;

    /**
     * @ORM\Column(name="permulaan", type="boolean", nullable=false, options={"default"=0})
     * @Expose
     *
     * @var boolean
     */
    private $permulaan = false;

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
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Kelas
     */
    private $kelas;

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
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="templatesms_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Templatesms
     */
    private $templatesms;

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
     * @param string $perulangan
     */
    public function setPerulangan($perulangan)
    {
        $this->perulangan = $perulangan;
    }

    /**
     * @return string
     */
    public function getPerulangan()
    {
        return $this->perulangan;
    }

    /**
     * @param integer $mingguanHariKe
     */
    public function setMingguanHariKe($mingguanHariKe)
    {
        $this->mingguanHariKe = $mingguanHariKe;
    }

    /**
     * @return integer
     */
    public function getMingguanHariKe()
    {
        return $this->mingguanHariKe;
    }

    /**
     * @param integer $bulananHariKe
     */
    public function setBulananHariKe($bulananHariKe)
    {
        $this->bulananHariKe = $bulananHariKe;
    }

    /**
     * @return integer
     */
    public function getBulananHariKe()
    {
        return $this->bulananHariKe;
    }

    /**
     * @param string $paramstatusDariJam
     */
    public function setParamstatusDariJam($paramstatusDariJam)
    {
        $this->paramstatusDariJam = $paramstatusDariJam;
    }

    /**
     * @param  boolean $withsecond
     * @return string
     */
    public function getParamstatusDariJam($withsecond = TRUE)
    {
        return !$withsecond ? substr($this->paramstatusDariJam, 0, 5) : $this->paramstatusDariJam;
    }

    /**
     * @param string $paramstatusHinggaJam
     */
    public function setParamstatusHinggaJam($paramstatusHinggaJam)
    {
        $this->paramstatusHinggaJam = $paramstatusHinggaJam;
    }

    /**
     * @param  boolean $withsecond
     * @return string
     */
    public function getParamstatusHinggaJam($withsecond = TRUE)
    {
        return !$withsecond ? substr($this->paramstatusHinggaJam, 0, 5) : $this->paramstatusHinggaJam;
    }

    /**
     * @param boolean $kirimSms
     */
    public function setKirimSms($kirimSms)
    {
        $this->kirimSms = $kirimSms;
    }

    /**
     * @return boolean
     */
    public function isKirimSms()
    {
        return $this->kirimSms;
    }

    /**
     * @param string $smsJam
     */
    public function setSmsJam($smsJam)
    {
        $this->smsJam = $smsJam;
    }

    /**
     * @return string
     */
    public function getSmsJam($withsecond = TRUE)
    {
        return !$withsecond ? substr($this->smsJam, 0, 5) : $this->smsJam;
    }

    /**
     * @param boolean $otomatisTerhubungMesin
     */
    public function setOtomatisTerhubungMesin($otomatisTerhubungMesin)
    {
        $this->otomatisTerhubungMesin = $otomatisTerhubungMesin;
    }

    /**
     * @return boolean
     */
    public function isOtomatisTerhubungMesin()
    {
        return $this->otomatisTerhubungMesin;
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
     * @param Templatesms $templatesms
     */
    public function setTemplatesms(Templatesms $templatesms = null)
    {
        $this->templatesms = $templatesms;
    }

    /**
     * @return Templatesms
     */
    public function getTemplatesms()
    {
        return $this->templatesms;
    }

    /**
     * Daftar status kehadiran yang bisa ada di database
     *
     * @return array
     */
    public static function getDaftarStatusKehadiran()
    {
        return [
            'a-hadir-tepat' => 'status.kehadiran.hadir.tepat',
            'b-hadir-telat' => 'status.kehadiran.hadir.telat',
            'c-alpa' => 'status.kehadiran.alpa',
            'd-izin' => 'status.kehadiran.izin',
            'e-sakit' => 'status.kehadiran.sakit',
        ];
    }

    /**
     * Daftar perulangan jadwal kehadiran
     *
     * @return array
     */
    public static function getDaftarPerulangan()
    {
        return [
            'a-harian' => 'Harian',
            'b-mingguan' => 'Mingguan',
            'c-bulanan' => 'Bulanan',
        ];
    }

    /**
     * Nama-nama hari dalam satu minggu, dimulai dari senin, array berbasis 0
     *
     * @return array
     */
    public static function getNamaHari()
    {
        return [
            0 => 'label.senin',
            'label.selasa',
            'label.rabu',
            'label.kamis',
            'label.jumat',
            'label.sabtu',
            'label.minggu',
        ];
    }

    /**
     * Angka-angka hari dalam satu bulan, dimulai dari 1
     *
     * @return array
     */
    public static function getAngkaHariSebulan()
    {
        return array_combine(range(1, 31), range(1, 31));
    }
}
