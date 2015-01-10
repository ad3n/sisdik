<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="proses_sms_periodik")
 * @ORM\Entity
 */
class ProsesSmsPeriodik
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
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="berhasil_kirim_sms", type="boolean", nullable=false, options={"default"="0"})
     *
     * @var boolean
     */
    private $berhasilKirimSms = false;

    /**
     * @ORM\ManyToOne(targetEntity="LayananSmsPeriodik")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="layanan_sms_periodik_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var LayananSmsPeriodik
     */
    private $layananSmsPeriodik;

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
     * @param \DateTime $tanggal
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    /**
     * @return \DateTime
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * @param boolean $berhasilKirimSms
     */
    public function setBerhasilKirimSms($berhasilKirimSms)
    {
        $this->berhasilKirimSms = $berhasilKirimSms;
    }

    /**
     * @return boolean
     */
    public function getBerhasilKirimSms()
    {
        return $this->berhasilKirimSms;
    }

    /**
     * @param LayananSmsPeriodik $layananSmsPeriodik
     */
    public function setLayananSmsPeriodik(LayananSmsPeriodik $layananSmsPeriodik = null)
    {
        $this->layananSmsPeriodik = $layananSmsPeriodik;
    }

    /**
     * @return LayananSmsPeriodik
     */
    public function getLayananSmsPeriodik()
    {
        return $this->layananSmsPeriodik;
    }

    /**
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * Get sekolah
     *
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }
}
