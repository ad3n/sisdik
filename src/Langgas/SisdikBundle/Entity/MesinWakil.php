<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mesin_wakil", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fk_mesin_wakil_sekolah1_idx", columns={"sekolah_id"})
 * })
 * @ORM\Entity
 */
class MesinWakil
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
     * @ORM\Column(name="url_kehadiran_manual", type="string", length=200, nullable=false, options={"default"="http://192.168.1.99/ambil-kehadiran-manual.php"})
     *
     * @var string
     */
    private $urlKehadiranManual = "http://192.168.1.99/ambil-kehadiran-manual.php";

    /**
     * @ORM\Column(name="url_kepulangan_manual", type="string", length=200, nullable=false, options={"default"="http://192.168.1.99/ambil-kepulangan-manual.php"})
     *
     * @var string
     */
    private $urlKepulanganManual = "http://192.168.1.99/ambil-kepulangan-manual.php";

    /**
     * @ORM\Column(name="url_jadwal_kehadiran", type="string", length=200, nullable=false, options={"default"="http://192.168.1.99/pembaruan-jadwal-kehadiran.php"})
     *
     * @var string
     */
    private $urlJadwalKehadiran = "http://192.168.1.99/pembaruan-jadwal-kehadiran.php";

    /**
     * @ORM\Column(name="url_jadwal_kepulangan", type="string", length=200, nullable=false, options={"default"="http://192.168.1.99/pembaruan-jadwal-kepulangan.php"})
     *
     * @var string
     */
    private $urlJadwalKepulangan = "http://192.168.1.99/pembaruan-jadwal-kepulangan.php";

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
     * @param string $urlKehadiranManual
     */
    public function setUrlKehadiranManual($urlKehadiranManual)
    {
        $this->urlKehadiranManual = $urlKehadiranManual;
    }

    /**
     * @return string
     */
    public function getUrlKehadiranManual()
    {
        return $this->urlKehadiranManual;
    }

    /**
     * @param string $urlKepulanganManual
     */
    public function setUrlKepulanganManual($urlKepulanganManual)
    {
        $this->urlKepulanganManual = $urlKepulanganManual;
    }

    /**
     * @return string
     */
    public function getUrlKepulanganManual()
    {
        return $this->urlKepulanganManual;
    }

    /**
     * @param string $urlJadwalKehadiran
     */
    public function setUrlJadwalKehadiran($urlJadwalKehadiran)
    {
        $this->urlJadwalKehadiran = $urlJadwalKehadiran;
    }

    /**
     * @return string
     */
    public function getUrlJadwalKehadiran()
    {
        return $this->urlJadwalKehadiran;
    }

    /**
     * @param string $urlJadwalKepulangan
     */
    public function setUrlJadwalKepulangan($urlJadwalKepulangan)
    {
        $this->urlJadwalKepulangan = $urlJadwalKepulangan;
    }

    /**
     * @return string
     */
    public function getUrlJadwalKepulangan()
    {
        return $this->urlJadwalKepulangan;
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
