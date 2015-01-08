<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="layanan_sms_periodik")
 * @ORM\Entity
 */
class LayananSmsPeriodik
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
     * @ORM\Column(name="jenis_layanan", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $jenisLayanan;

    /**
     * @ORM\Column(name="perulangan", type="string", length=100, nullable=false)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $perulangan;

    /**
     * @ORM\Column(name="mingguan_hari_ke", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $mingguanHariKe;

    /**
     * @ORM\Column(name="bulanan_hari_ke", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $bulananHariKe;

    /**
     * @ORM\Column(name="bulan_awal", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $bulanAwal;

    /**
     * @ORM\Column(name="sms_jam", type="string", length=50, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $smsJam;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"="0"})
     *
     * @var boolean
     */
    private $aktif = false;

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
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="templatesms_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Templatesms
     */
    private $templatesms;

    /**
     * @ORM\ManyToOne(targetEntity="Tingkat")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tingkat_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Tingkat
     */
    private $tingkat;

    /**
     * Daftar perulangan yang mungkin digunakan untuk layanan sms periodik
     *
     * @return array
     */
    public static function getDaftarPerulangan()
    {
        return [
            'a-harian' => 'Harian',
            'b-mingguan' => 'Mingguan',
            'c-bulanan' => 'Bulanan',
            'd-triwulan' => 'Triwulan',
            'e-caturwulan' => 'Caturwulan',
            'f-semester' => 'Semester',
            'g-tahunan' => 'Tahunan',
        ];
    }

    /**
     * Daftar nama-nama bulan dalam setahun
     *
     * @return array
     */
    public static function getDaftarNamaBulan()
    {
        return [
            1 => 'label.januari',
            'label.februari',
            'label.maret',
            'label.april',
            'label.mei',
            'label.juni',
            'label.juli',
            'label.agustus',
            'label.september',
            'label.oktober',
            'label.november',
            'label.desember',
        ];
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $jenisLayanan
     */
    public function setJenisLayanan($jenisLayanan)
    {
        $this->jenisLayanan = $jenisLayanan;
    }

    /**
     * @return string
     */
    public function getJenisLayanan()
    {
        return $this->jenisLayanan;
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
     * @param integer $bulanAwal
     */
    public function setBulanAwal($bulanAwal)
    {
        $this->bulanAwal = $bulanAwal;
    }

    /**
     * @return integer
     */
    public function getBulanAwal()
    {
        return $this->bulanAwal;
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
    public function getSmsJam($withsecond = true)
    {
        return !$withsecond ? substr($this->smsJam, 0, 5) : $this->smsJam;
    }

    /**
     * @param boolean $aktif
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    }

    /**
     * @return boolean
     */
    public function getAktif()
    {
        return $this->aktif;
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
     * @param Tingkat $tingkat
     */
    public function setTingkat(Tingkat $tingkat = null)
    {
        $this->tingkat = $tingkat;
    }

    /**
     * @return Tingkat
     */
    public function getTingkat()
    {
        return $this->tingkat;
    }
}
