<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mesin_wakil")
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
