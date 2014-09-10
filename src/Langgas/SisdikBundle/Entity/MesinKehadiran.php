<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;

/**
 * @ORM\Table(name="mesin_kehadiran")
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class MesinKehadiran
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
     * @ORM\Column(name="alamat_ip", type="string", length=45, nullable=false)
     * @Expose
     * @SerializedName("alamat_ip")
     *
     * @var string
     */
    private $alamatIp;

    /**
     * @ORM\Column(name="commkey", type="string", length=45, nullable=false)
     * @Expose
     *
     * @var string
     */
    private $commkey;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"=1})
     * @Expose
     * @SerializedName("aktif")
     *
     * @var boolean
     */
    private $aktif = true;

    /**
     * @ORM\Column(name="web_username", type="string", length=50, nullable=false, options={"default"="administrator"})
     * @Expose
     * @SerializedName("web_username")
     *
     * @var string
     */
    private $webUsername;

    /**
     * @ORM\Column(name="web_password", type="string", length=50, nullable=false, options={"default"="123456"})
     * @Expose
     * @SerializedName("web_password")
     *
     * @var string
     */
    private $webPassword;

    /**
     * @ORM\Column(name="waktu_hapus_harian", type="string", length=50, nullable=true)
     * @Expose
     * @SerializedName("waktu_hapus_harian")
     *
     * @var string
     */
    private $waktuHapusHarian;

    /**
     * @ORM\Column(name="waktu_cadang_harian", type="string", length=50, nullable=true)
     * @Expose
     * @SerializedName("waktu_cadang_harian")
     *
     * @var string
     */
    private $waktuCadangHarian;

    /**
     * @ORM\Column(name="waktu_tertib_harian", type="string", length=50, nullable=true)
     * @Expose
     * @SerializedName("waktu_tertib_harian")
     *
     * @var string
     */
    private $waktuTertibHarian;

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
     * @param string $alamatIp
     */
    public function setAlamatIp($alamatIp)
    {
        $this->alamatIp = $alamatIp;
    }

    /**
     * @return string
     */
    public function getAlamatIp()
    {
        return $this->alamatIp;
    }

    /**
     * @param string $commkey
     */
    public function setCommkey($commkey)
    {
        $this->commkey = $commkey;
    }

    /**
     * @return string
     */
    public function getCommkey()
    {
        return $this->commkey;
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
     * @param string $waktuHapusHarian
     */
    public function setWaktuHapusHarian($waktuHapusHarian)
    {
        $this->waktuHapusHarian = $waktuHapusHarian;
    }

    /**
     * @return string
     */
    public function getWaktuHapusHarian()
    {
        return $this->waktuHapusHarian;
    }

    /**
     * @param string $waktuCadangHarian
     */
    public function setWaktuCadangHarian($waktuCadangHarian)
    {
        $this->waktuCadangHarian = $waktuCadangHarian;
    }

    /**
     * @return string
     */
    public function getWaktuCadangHarian()
    {
        return $this->waktuCadangHarian;
    }

    /**
     * @param string $waktuTertibHarian
     */
    public function setWaktuTertibHarian($waktuTertibHarian)
    {
        $this->waktuTertibHarian = $waktuTertibHarian;
    }

    /**
     * @return string
     */
    public function getWaktuTertibHarian()
    {
        return $this->waktuTertibHarian;
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
     * @param string $webUsername
     */
    public function setWebUsername($webUsername)
    {
        $this->webUsername = $webUsername;
    }

    /**
     * @return string
     */
    public function getWebUsername()
    {
        return $this->webUsername;
    }

    /**
     * @param string $webPassword
     */
    public function setWebPassword($webPassword)
    {
        $this->webPassword = $webPassword;
    }

    /**
     * @return string
     */
    public function getWebPassword()
    {
        return $this->webPassword;
    }
}
