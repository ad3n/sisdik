<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="pembayaran_rutin")
 * @ORM\Entity
 */
class PembayaranRutin
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
     * @ORM\Column(name="daftar_biaya_rutin", type="array", nullable=true)
     *
     * @var array
     */
    private $daftarBiayaRutin;

    /**
     * @ORM\Column(name="nominal_total", type="bigint", nullable=true)
     *
     * @var integer
     */
    private $nominalTotal;

    /**
     * @ORM\Column(name="keterangan", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    private $waktuSimpan;

    /**
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @var \DateTime
     */
    private $waktuUbah;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pembayaranRutin")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @ORM\OneToMany(targetEntity="TransaksiPembayaranRutin", mappedBy="pembayaranRutin", cascade={"persist"})
     * @ORM\OrderBy({"waktuSimpan" = "ASC"})
     * @Assert\Valid
     *
     * @var TransaksiPembayaranRutin
     */
    private $transaksiPembayaranRutin;

    public function __construct()
    {
        $this->transaksiPembayaranRutin = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $daftarBiayaRutin
     */
    public function setDaftarBiayaRutin($daftarBiayaRutin)
    {
        $this->daftarBiayaRutin = $daftarBiayaRutin;
    }

    /**
     * @return array
     */
    public function getDaftarBiayaRutin()
    {
        return $this->daftarBiayaRutin;
    }

    /**
     * @param integer $nominalTotal
     */
    public function setNominalTotal($nominalTotal)
    {
        $this->nominalTotal = $nominalTotal;
    }

    /**
     * @return integer
     */
    public function getNominalTotal()
    {
        return $this->nominalTotal;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param \DateTime $waktuSimpan
     */
    public function setWaktuSimpan($waktuSimpan)
    {
        $this->waktuSimpan = $waktuSimpan;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * @param \DateTime $waktuUbah
     */
    public function setWaktuUbah($waktuUbah)
    {
        $this->waktuUbah = $waktuUbah;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuUbah()
    {
        return $this->waktuUbah;
    }

    /**
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }

    /**
     * Menentukan transaksi pembayaran rutin.
     * Type hinting ArrayCollection dihapus agar bisa melakukan pengubahan.
     *
     * @param ArrayCollection $transaksiPembayaranRutin
     */
    public function setTransaksiPembayaranRutin($transaksiPembayaranRutin)
    {
        foreach ($transaksiPembayaranRutin as $transaksi) {
            $transaksi->setPembayaranSekali($this);
        }

        $this->transaksiPembayaranRutin = $transaksiPembayaranRutin;
    }

    /**
     * @return TransaksiPembayaranRutin
     */
    public function getTransaksiPembayaranRutin()
    {
        return $this->transaksiPembayaranRutin;
    }
}
