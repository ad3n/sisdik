<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * LogsmsKeluar
 *
 * @ORM\Table(name="logsms_keluar")
 * @ORM\Entity
 */
class LogsmsKeluar
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="penyedia_api", type="string", length=200, nullable=true)
     */
    private $penyediaApi;

    /**
     * @var string
     *
     * @ORM\Column(name="api_terpanggil", type="text", nullable=true)
     */
    private $apiTerpanggil;

    /**
     * @var string
     *
     * @ORM\Column(name="ke", type="string", length=50, nullable=true)
     */
    private $ke;

    /**
     * @var string
     *
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     */
    private $teks;

    /**
     * @var integer
     *
     * @ORM\Column(name="dlr", type="smallint", nullable=true)
     */
    private $dlr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dlrtime", type="datetime", nullable=true)
     */
    private $dlrtime;

    /**
     * @var string
     *
     * @ORM\Column(name="hasil_api", type="text", nullable=true)
     */
    private $hasilApi;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_panggil_api", type="datetime", nullable=true)
     */
    private $waktuPanggilApi;

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
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set penyediaApi
     *
     * @param string $penyediaApi
     * @return LogsmsKeluar
     */
    public function setPenyediaApi($penyediaApi) {
        $this->penyediaApi = $penyediaApi;

        return $this;
    }

    /**
     * Get penyediaApi
     *
     * @return string
     */
    public function getPenyediaApi() {
        return $this->penyediaApi;
    }

    /**
     * Set apiTerpanggil
     *
     * @param string $apiTerpanggil
     * @return LogsmsKeluar
     */
    public function setApiTerpanggil($apiTerpanggil) {
        $this->apiTerpanggil = $apiTerpanggil;

        return $this;
    }

    /**
     * Get apiTerpanggil
     *
     * @return string
     */
    public function getApiTerpanggil() {
        return $this->apiTerpanggil;
    }

    /**
     * Set ke
     *
     * @param string $ke
     * @return LogsmsKeluar
     */
    public function setKe($ke) {
        $this->ke = $ke;

        return $this;
    }

    /**
     * Get ke
     *
     * @return string
     */
    public function getKe() {
        return $this->ke;
    }

    /**
     * Set teks
     *
     * @param string $teks
     * @return LogsmsKeluar
     */
    public function setTeks($teks) {
        $this->teks = $teks;

        return $this;
    }

    /**
     * Get teks
     *
     * @return string
     */
    public function getTeks() {
        return $this->teks;
    }

    /**
     * Set dlr
     *
     * @param integer $dlr
     * @return LogsmsKeluar
     */
    public function setDlr($dlr) {
        $this->dlr = $dlr;

        return $this;
    }

    /**
     * Get dlr
     *
     * @return integer
     */
    public function getDlr() {
        return $this->dlr;
    }

    /**
     * Set dlrtime
     *
     * @param \DateTime $dlrtime
     * @return LogsmsKeluar
     */
    public function setDlrtime($dlrtime) {
        $this->dlrtime = $dlrtime;

        return $this;
    }

    /**
     * Get dlrtime
     *
     * @return \DateTime
     */
    public function getDlrtime() {
        return $this->dlrtime;
    }

    /**
     * Set hasilApi
     *
     * @param string $hasilApi
     * @return LogsmsKeluar
     */
    public function setHasilApi($hasilApi) {
        $this->hasilApi = $hasilApi;

        return $this;
    }

    /**
     * Get hasilApi
     *
     * @return string
     */
    public function getHasilApi() {
        return $this->hasilApi;
    }

    /**
     * Set waktuPanggilApi
     *
     * @param \DateTime $waktuPanggilApi
     * @return LogsmsKeluar
     */
    public function setWaktuPanggilApi($waktuPanggilApi) {
        $this->waktuPanggilApi = $waktuPanggilApi;

        return $this;
    }

    /**
     * Get waktuPanggilApi
     *
     * @return \DateTime
     */
    public function getWaktuPanggilApi() {
        return $this->waktuPanggilApi;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return LogsmsKeluar
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
}
