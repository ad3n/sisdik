<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="daftar_biaya_pendaftaran")
 * @ORM\Entity
 */
class DaftarBiayaPendaftaran
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
     * @ORM\ManyToOne(targetEntity="PembayaranPendaftaran", inversedBy="daftarBiayaPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pembayaran_pendaftaran_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var PembayaranPendaftaran
     */
    private $pembayaranPendaftaran;

    /**
     * @ORM\ManyToOne(targetEntity="BiayaPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="biaya_pendaftaran_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var BiayaPendaftaran
     */
    private $biayaPendaftaran;

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
     * @param PembayaranPendaftaran $pembayaranPendaftaran
     */
    public function setPembayaranPendaftaran(PembayaranPendaftaran $pembayaranPendaftaran = null)
    {
        $this->pembayaranPendaftaran = $pembayaranPendaftaran;
    }

    /**
     * @return PembayaranPendaftaran
     */
    public function getPembayaranPendaftaran()
    {
        return $this->pembayaranPendaftaran;
    }

    /**
     * @param BiayaPendaftaran $biayaPendaftaran
     */
    public function setBiayaPendaftaran(BiayaPendaftaran $biayaPendaftaran = null)
    {
        $this->biayaPendaftaran = $biayaPendaftaran;
    }

    /**
     * @return BiayaPendaftaran
     */
    public function getBiayaPendaftaran()
    {
        return $this->biayaPendaftaran;
    }
}
