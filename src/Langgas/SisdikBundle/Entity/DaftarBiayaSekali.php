<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="daftar_biaya_sekali")
 * @ORM\Entity
 */
class DaftarBiayaSekali
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
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $nama;

    /**
     * @var boolean
     */
    private $terpilih;

    /**
     * @ORM\Column(name="nominal", type="bigint", nullable=false, options={"default" = 0})
     * @Assert\NotBlank
     *
     * @var integer
     */
    private $nominal;

    /**
     * @ORM\ManyToOne(targetEntity="PembayaranSekali", inversedBy="daftarBiayaSekali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="pembayaran_sekali_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var PembayaranSekali
     */
    private $pembayaranSekali;

    /**
     * @ORM\ManyToOne(targetEntity="BiayaSekali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="biaya_sekali_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var BiayaSekali
     */
    private $biayaSekali;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $nama
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    }

    /**
     * @return string
     */
    public function getNama()
    {
        return $this->nama;
    }

    /**
     * Mengambil daftar nama hanya untuk tampilan
     *
     * @return string
     */
    public function getNamaDisplay()
    {
        return strlen($this->nama) > 20 ? substr($this->nama, 0, 17) . '...' : $this->nama;
    }

    /**
     * @param boolean $terpilih
     */
    public function setTerpilih($terpilih)
    {
        $this->terpilih = $terpilih;
    }

    /**
     * @return boolean
     */
    public function isTerpilih()
    {
        return $this->terpilih;
    }

    /**
     * @param integer $nominal
     */
    public function setNominal($nominal)
    {
        $this->nominal = $nominal;
    }

    /**
     * @return integer
     */
    public function getNominal()
    {
        return $this->nominal;
    }

    /**
     * @param PembayaranSekali $pembayaranSekali
     */
    public function setPembayaranSekali(PembayaranSekali $pembayaranSekali = null)
    {
        $this->pembayaranSekali = $pembayaranSekali;
    }

    /**
     * @return PembayaranSekali
     */
    public function getPembayaranSekali()
    {
        return $this->pembayaranSekali;
    }

    /**
     * @param BiayaSekali $biayaSekali
     */
    public function setBiayaSekali(BiayaSekali $biayaSekali = null)
    {
        $this->biayaSekali = $biayaSekali;
    }

    /**
     * @return BiayaSekali
     */
    public function getBiayaSekali()
    {
        return $this->biayaSekali;
    }
}
