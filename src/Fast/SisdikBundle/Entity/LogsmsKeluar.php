<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="logsms_keluar")
 * @ORM\Entity
 */
class LogsmsKeluar
{
    /**
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="penyedia_api", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $penyediaApi;

    /**
     * @ORM\Column(name="api_terpanggil", type="text", nullable=true)
     *
     * @var string
     */
    private $apiTerpanggil;

    /**
     * @ORM\Column(name="ke", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $ke;

    /**
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $teks;

    /**
     * @ORM\Column(name="dlr", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $dlr;

    /**
     * @ORM\Column(name="dlrtime", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $dlrtime;

    /**
     * @ORM\Column(name="hasil_api", type="text", nullable=true)
     *
     * @var string
     */
    private $hasilApi;

    /**
     * @ORM\Column(name="waktu_panggil_api", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $waktuPanggilApi;

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
     * @param string $penyediaApi
     */
    public function setPenyediaApi($penyediaApi)
    {
        $this->penyediaApi = $penyediaApi;
    }

    /**
     * @return string
     */
    public function getPenyediaApi()
    {
        return $this->penyediaApi;
    }

    /**
     * @param string $apiTerpanggil
     */
    public function setApiTerpanggil($apiTerpanggil)
    {
        $this->apiTerpanggil = $apiTerpanggil;
    }

    /**
     * @return string
     */
    public function getApiTerpanggil()
    {
        return $this->apiTerpanggil;
    }

    /**
     * @param string $ke
     */
    public function setKe($ke)
    {
        $this->ke = $ke;
    }

    /**
     * @return string
     */
    public function getKe()
    {
        return $this->ke;
    }

    /**
     * @param string $teks
     */
    public function setTeks($teks)
    {
        $this->teks = $teks;
    }

    /**
     * @return string
     */
    public function getTeks()
    {
        return $this->teks;
    }

    /**
     * @param integer $dlr
     */
    public function setDlr($dlr)
    {
        $this->dlr = $dlr;
    }

    /**
     * @return integer
     */
    public function getDlr()
    {
        return $this->dlr;
    }

    /**
     * @param \DateTime $dlrtime
     */
    public function setDlrtime($dlrtime)
    {
        $this->dlrtime = $dlrtime;
    }

    /**
     * @return \DateTime
     */
    public function getDlrtime()
    {
        return $this->dlrtime;
    }

    /**
     * @param string $hasilApi
     */
    public function setHasilApi($hasilApi)
    {
        $this->hasilApi = $hasilApi;
    }

    /**
     * @return string
     */
    public function getHasilApi()
    {
        return $this->hasilApi;
    }

    /**
     * @param \DateTime $waktuPanggilApi
     */
    public function setWaktuPanggilApi($waktuPanggilApi)
    {
        $this->waktuPanggilApi = $waktuPanggilApi;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuPanggilApi()
    {
        return $this->waktuPanggilApi;
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
