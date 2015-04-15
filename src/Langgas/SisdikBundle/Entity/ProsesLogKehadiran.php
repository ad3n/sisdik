<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="proses_log_kehadiran")
 * @ORM\Entity
 */
class ProsesLogKehadiran
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
     * @ORM\Column(name="nama_file", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $namaFile;

    /**
     * @ORM\Column(name="prioritas", type="string", length=50, nullable=true)
     *
     * @var integer
     */
    private $prioritas;

    /**
     * @ORM\Column(name="status_antrian", type="string", length=50, nullable=true)
     *
     * @var integer
     */
    private $statusAntrian;

    /**
     * @ORM\Column(name="awal_proses", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $awalProses;

    /**
     * @ORM\Column(name="akhir_proses", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $akhirProses;

    /**
     * @ORM\Column(name="jumlah_log_diproses", type="integer", nullable=true)
     *
     * @var integer
     */
    private $jumlahLogDiproses;

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
     * @param string $namaFile
     */
    public function setNamaFile($namaFile)
    {
        $this->namaFile = $namaFile;
    }

    /**
     * @return string
     */
    public function getNamaFile()
    {
        return $this->namaFile;
    }

    /**
     * @param string $prioritas
     */
    public function setPrioritas($prioritas)
    {
        $this->prioritas = $prioritas;
    }

    /**
     * @return string
     */
    public function getPrioritas()
    {
        return $this->prioritas;
    }

    /**
     * @param string $statusAntrian
     */
    public function setStatusAntrian($statusAntrian)
    {
        $this->statusAntrian = $statusAntrian;
    }

    /**
     * @return string
     */
    public function getStatusAntrian()
    {
        return $this->statusAntrian;
    }

    /**
     * @param \DateTime $awalProses
     */
    public function setAwalProses($awalProses)
    {
        $this->awalProses = $awalProses;
    }

    /**
     * @return \DateTime
     */
    public function getAwalProses()
    {
        return $this->awalProses;
    }

    /**
     * @param \DateTime $akhirProses
     */
    public function setAkhirProses($akhirProses)
    {
        $this->akhirProses = $akhirProses;
    }

    /**
     * @return \DateTime
     */
    public function getAkhirProses()
    {
        return $this->akhirProses;
    }

    /**
     * @param integer $jumlahLogDiproses
     */
    public function setJumlahLogDiproses($jumlahLogDiproses)
    {
        $this->jumlahLogDiproses = $jumlahLogDiproses;
    }

    /**
     * @return integer
     */
    public function getJumlahLogDiproses()
    {
        return $this->jumlahLogDiproses;
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

    /**
     * Daftar status antrian kehadiran yang dapat digunakan.
     *
     * @return array
     */
    public static function getDaftarStatusAntrian()
    {
        return [
            'a-masuk-antrian' => 'proses.log.hadir.antri',
            'b-sedang-dikerjakan' => 'proses.log.hadir.dikerjakan',
            'c-selesai' => 'proses.log.hadir.selesai',
        ];
    }
}
