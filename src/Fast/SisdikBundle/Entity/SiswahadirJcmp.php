<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="siswahadir_jcmp")
 * @ORM\Entity
 */
class SiswahadirJcmp
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
     * @ORM\Column(name="hadir", type="boolean", nullable=true)
     *
     * @var boolean
     */
    private $hadir;

    /**
     * @ORM\Column(name="jam_masuk", type="string", length=20, nullable=true)
     *
     * @var string
     */
    private $jamMasuk;

    /**
     * @ORM\Column(name="jam_keluar", type="string", length=20, nullable=true)
     *
     * @var string
     */
    private $jamKeluar;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="JadwalCmp")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jadwal_cmp_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var JadwalCmp
     */
    private $jadwalCmp;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $hadir
     */
    public function setHadir($hadir)
    {
        $this->hadir = $hadir;
    }

    /**
     * @return boolean
     */
    public function getHadir()
    {
        return $this->hadir;
    }

    /**
     * @param string $jamMasuk
     */
    public function setJamMasuk($jamMasuk)
    {
        $this->jamMasuk = $jamMasuk;
    }

    /**
     * @return string
     */
    public function getJamMasuk()
    {
        return $this->jamMasuk;
    }

    /**
     * @param string $jamKeluar
     */
    public function setJamKeluar($jamKeluar)
    {
        $this->jamKeluar = $jamKeluar;
    }

    /**
     * @return string
     */
    public function getJamKeluar()
    {
        return $this->jamKeluar;
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
     * @param JadwalCmp $jadwalCmp
     */
    public function setJadwalCmp(JadwalCmp $jadwalCmp = null)
    {
        $this->jadwalCmp = $jadwalCmp;
    }

    /**
     * @return JadwalCmp
     */
    public function getJadwalCmp()
    {
        return $this->jadwalCmp;
    }
}
