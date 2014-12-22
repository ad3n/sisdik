<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="pilihan_layanan_sms", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="pilihan_layanan_sms_UNIQUE", columns={"sekolah_id", "jenis_layanan"})
 * })
 * @ORM\Entity
 */
class PilihanLayananSms
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
     * @ORM\Column(name="jenis_layanan", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $jenisLayanan;

    /**
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default"="1"})
     *
     * @var boolean
     */
    private $status = true;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Daftar layanan sms pendaftaran yang mungkin digunakan.
     *
     * @return array
     */
    public static function getDaftarLayananPendaftaran()
    {
        return [
            'a-pendaftaran-tercatat' => 'layanan.sms.pendaftar.tercatat',
            'b-pendaftaran-bayar-pertama' => 'layanan.sms.pendaftar.bayar.pertama',
            'c-pendaftaran-bayar' => 'layanan.sms.pendaftar.bayar.biaya',
            'd-pendaftaran-bayar-lunas' => 'layanan.sms.pendaftar.bayar.lunas',
        ];
    }

    /**
     * Daftar layanan sms laporan yang mungkin digunakan.
     *
     * @return array
     */
    public static function getDaftarLayananLaporan()
    {
        return [
            'e-laporan-ringkasan' => 'layanan.sms.laporan.ringkasan.pendaftaran',
        ];
    }

    /**
     * Daftar layanan sms kehadiran.
     *
     * @return array
     */
    public static function getDaftarLayananKehadiran()
    {
        return [
            'k-kehadiran-alpa' => 'layanan.sms.kehadiran.alpa',
            'l-kehadiran-tepat' => 'layanan.sms.kehadiran.tepat',
            'm-kehadiran-telat' => 'layanan.sms.kehadiran.telat',
            'n-kehadiran-izin' => 'layanan.sms.kehadiran.izin',
            'o-kehadiran-sakit' => 'layanan.sms.kehadiran.sakit',
        ];
    }

    /**
     * Daftar layanan sms kepulangan.
     *
     * @return array
     */
    public static function getDaftarLayananKepulangan()
    {
        return [
            'u-kepulangan-tercatat' => 'layanan.sms.kepulangan.tercatat',
            'v-kepulangan-tak-tercatat' => 'layanan.sms.kepulangan.tak.tercatat',
        ];
    }

    /**
     * Daftar layanan sms biaya sekali bayar yang mungkin digunakan.
     *
     * @return array
     */
    public static function getDaftarLayananBiayaSekaliBayar()
    {
        return [
            'aa-biaya-sekali-bayar' => 'layanan.sms.bayar.biaya.sekali',
            'ab-biaya-sekali-bayar-lunas' => 'layanan.sms.lunas.bayar.biaya.sekali',
        ];
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
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
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
}
