<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="vendor_sekolah")
 * @ORM\Entity
 */
class VendorSekolah
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
     * @ORM\Column(name="jenis", type="string", length=50, nullable=false, options={"default"="standar"})
     *
     * @var string
     */
    private $jenis = "standar";

    /**
     * @ORM\Column(name="url_pengirim_pesan", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $urlPengirimPesan;

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
     * @param string $jenis
     */
    public function setJenis($jenis)
    {
        $this->jenis = $jenis;
    }

    /**
     * @return string
     */
    public function getJenis()
    {
        return $this->jenis;
    }

    /**
     * @param string $urlPengirimPesan
     */
    public function setUrlPengirimPesan($urlPengirimPesan)
    {
        $this->urlPengirimPesan = $urlPengirimPesan;
    }

    /**
     * @return string
     */
    public function getUrlPengirimPesan()
    {
        return $this->urlPengirimPesan;
    }

    /**
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah)
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
